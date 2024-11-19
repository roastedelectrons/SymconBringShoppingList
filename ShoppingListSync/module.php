<?php

declare(strict_types=1);

class ShoppingListSync extends IPSModule
{

	private $supportedModules = ['{7129178B-E633-238A-0851-2F1B5A09805E}', '{433E041A-6A52-19C4-2F02-195AB45F382F}']; // EchoRemote AlexaList, BringShoppingList

    public function Create()
    {
        //Never delete this line!
        parent::Create();
		$this->RegisterPropertyInteger('InstanceID1', 0);
		$this->RegisterPropertyInteger('InstanceID2', 0);
		$this->RegisterPropertyInteger('SyncMode', 1);
		$this->RegisterPropertyInteger('SyncInterval', 0);

		$this->RegisterAttributeString('Buffer', '');

		$this->RegisterTimer('Sync', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "Sync", "");');

        //we will wait until the kernel is ready
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    public function Destroy() {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        if (IPS_GetKernelRunlevel() !== KR_READY) {
            return;
        }

		$InstanceID1 = $this->ReadPropertyInteger('InstanceID1');
		$InstanceID2 = $this->ReadPropertyInteger('InstanceID2');

		if ($InstanceID1 > 1 ){
			$this->RegisterReference($InstanceID1);
		} else {
            $this->UnregisterReference($InstanceID1);
		}

		if ($InstanceID2 > 1 ){
			$this->RegisterReference($InstanceID2);
		} else {
            $this->UnregisterReference($InstanceID2);
		}

		if ($InstanceID1 > 1  && $InstanceID2 > 1 ){
			$this->SetTimerInterval('Sync', $this->ReadPropertyInteger('SyncInterval') * 1000);
		} else {
			$this->SetTimerInterval('Sync', 0);
		}


		if ( $this->ReadPropertyInteger('SyncMode') != 2){
			$this->WriteAttributeString('Buffer', '');
		}

    }


    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        switch ($Message) {
            case IM_CHANGESTATUS:
                if ($Data[0] === IS_ACTIVE) {
                    $this->ApplyChanges();
                }
                break;

            case IPS_KERNELMESSAGE:
                if ($Data[0] === KR_READY) {
                    $this->ApplyChanges();
                }
                break;

            default:
                break;
        }
    }

    public function RequestAction($ident, $value)
    {

        switch ($ident) {
			// Timer and Configuration form
			case 'Sync':
				$this->Sync();
				break;
        }

    }


	public function Sync()
	{

		$InstanceID1 = $this->ReadPropertyInteger('InstanceID1');
		$InstanceID2 = $this->ReadPropertyInteger('InstanceID2');

		if (!IPS_InstanceExists($InstanceID1) || !IPS_InstanceExists($InstanceID2)){
			$this->SendDebug( __FUNCTION__, 'Instance does not exist', 0);
			return false;
		}

		$semaphore = 'BringShoppingList.Sync.'.$this->InstanceID;

		if (IPS_SemaphoreEnter($semaphore, 1000) === false)
			return false;
			
		$this->SendDebug( __FUNCTION__, 'Start', 0);

		switch ($this->ReadPropertyInteger('SyncMode')) {

			case 1:
				// Transfer
				$items = $this->getItemNames($InstanceID1);

				foreach($items as $item){
					$result = $this->addItem($InstanceID2, $item);
					if ($result){
						$this->deleteItem($InstanceID1, $item);
					}
				}
				$this->updateList($InstanceID1);
				$this->updateList($InstanceID2);

				/*
				if (count($listItems) > 0){
					$this->Update();
					ALEXALIST_Update($alexaListInstance);
					$this->LogMessage('Synchronized '.count($alexaListItems).' item(s) from Alexa to Bring shopping list: '. implode(', ', array_column($alexaListItems, 'value') ) , KL_MESSAGE);
					$this->SendDebug( __FUNCTION__, 'Synchronized items: '. json_encode($alexaListItems), 0);
				} else{
					$this->SendDebug( __FUNCTION__, 'No items to sync', 0);
				}
				*/
				break;

			case 2:
				// Sync
				$list1Changes = $this->getChanges($InstanceID1);
				$list2Changes = $this->getChanges($InstanceID2);

				$this->executeChanges($InstanceID2, $list1Changes);
				$this->executeChanges($InstanceID1, $list2Changes);

				$this->setSyncBuffer($InstanceID1, $this->getItems($InstanceID1));
				$this->setSyncBuffer($InstanceID2, $this->getItems($InstanceID2));
				break;

		}

		$this->SendDebug( __FUNCTION__, 'End', 0);
		IPS_SemaphoreLeave($semaphore);

	}

	private function getChanges($instanceID){

		$itemsBuffer = $this->getSyncBuffer($instanceID);
	
		$items = $this->getItems($instanceID);
	
		$changes['items'] = $items;
		$changes['deleted'] = $this->getDeletedItems($instanceID, $items, $itemsBuffer);
		$changes['added'] = $this->getAddedItems($instanceID, $items, $itemsBuffer);
	
		return $changes;
	}
	
	private function executeChanges($instanceID, $changes){
		$this->deleteItems($instanceID, $changes['deleted']);
		$this->addItems($instanceID, $changes['added']);
	}
	
	private function getSyncBuffer($instanceID){
		$buffer = json_decode($this->ReadAttributeString('Buffer'), true);
		$instanceBuffer = [];
	
		if (isset($buffer[$instanceID])) {
			$instanceBuffer = $buffer[$instanceID];
		}
		return $instanceBuffer;
	}
	
	private function setSyncBuffer($instanceID, $value){
		$buffer = json_decode($this->ReadAttributeString('Buffer'), true);
		$buffer[$instanceID] = $value;
		$this->WriteAttributeString('Buffer', json_encode($buffer));
	}
	
	private function updateList($instanceID){
		switch (IPS_GetInstance($instanceID)['ModuleInfo']['ModuleID']){ 
			// Bring
			case "{433E041A-6A52-19C4-2F02-195AB45F382F}":
				$items = BringList_Update($instanceID);
				break;
			// Alexa
			case "{7129178B-E633-238A-0851-2F1B5A09805E}":
				$items = ALEXALIST_Update($instanceID);
				break;           
		}
		return $items;
	}
	
	private function getItems($instanceID){
		switch (IPS_GetInstance($instanceID)['ModuleInfo']['ModuleID']){ 
			// Bring
			case "{433E041A-6A52-19C4-2F02-195AB45F382F}":
				$items = BringList_GetItems($instanceID, false);
				break;
			// Alexa
			case "{7129178B-E633-238A-0851-2F1B5A09805E}":
				$items = ALEXALIST_GetItems($instanceID, false);
				break;           
		}
		return $items;
	}

	private function getItemNames($instanceID){

		$items = $this->getItems($instanceID);

		switch (IPS_GetInstance($instanceID)['ModuleInfo']['ModuleID']){
			// Bring
			case "{433E041A-6A52-19C4-2F02-195AB45F382F}":
				$itemName = 'name';
				break;
			// Alexa
			case "{7129178B-E633-238A-0851-2F1B5A09805E}":
				$itemName = 'value';
				break;           
		}

		$itemNames = array_column($items, $itemName);

		return $itemNames;

	}
	
	private function deleteItems($instanceID, $items){
		foreach($items as $item){
			$this->deleteItem($instanceID, $item);
		}
	}

	private function deleteItem($instanceID, $item){
		$result = false;

		$this->SendDebug( __FUNCTION__, 'INSTANCE: '.$instanceID.'| DELETE: '.$item, 0);
		switch (IPS_GetInstance($instanceID)['ModuleInfo']['ModuleID']){
			// Bring
			case "{433E041A-6A52-19C4-2F02-195AB45F382F}":
				$result = BringList_DeleteItem($instanceID, $item);
				$this->SendDebug( __FUNCTION__, 'BRING | DELETE: '.$item, 0);
				break;
			// Alexa
			case "{7129178B-E633-238A-0851-2F1B5A09805E}":
				$result = ALEXALIST_DeleteItem($instanceID, $item);
				$this->SendDebug( __FUNCTION__, 'ALEXA | DELETE: '.$item, 0);
				break;           
		}
		
		return $result;

	}

	
	private function addItems($instanceID, $items){
		foreach($items as $item){
			$this->addItem($instanceID, $item);
		}
	}

	private function addItem($instanceID, $item){
		$result = false;

		switch (IPS_GetInstance($instanceID)['ModuleInfo']['ModuleID']){
			// Bring
			case "{433E041A-6A52-19C4-2F02-195AB45F382F}":
				$result = BringList_AddItem($instanceID, $item, '');
				break;
			// Alexa
			case "{7129178B-E633-238A-0851-2F1B5A09805E}":
				$result = ALEXALIST_AddItem($instanceID, $item);
				break;           
		}	

		return $result;
	}
	
	private function getDeletedItems( $instanceID, $items, $itemsLastSync){
	
		switch (IPS_GetInstance($instanceID)['ModuleInfo']['ModuleID']){
			// Bring
			case "{433E041A-6A52-19C4-2F02-195AB45F382F}":
				$itemName = 'name';
				break;
			// Alexa
			case "{7129178B-E633-238A-0851-2F1B5A09805E}":
				$itemName = 'value';
				break;           
		}
	
		$itemKeys = array_column($items, $itemName);
	
		$deleted = array();
	
		foreach ($itemsLastSync as $item){
			if (!$this->in_arrayi($item[$itemName], $itemKeys)){
				$deleted[] = $item[$itemName];
			}
		}
		return $deleted;
	}
	
	private function getAddedItems( $instanceID, $items, $itemsLastSync ){
	
		switch (IPS_GetInstance($instanceID)['ModuleInfo']['ModuleID']){
			// Bring
			case "{433E041A-6A52-19C4-2F02-195AB45F382F}":
				$itemName = 'name';
				break;
			// Alexa
			case "{7129178B-E633-238A-0851-2F1B5A09805E}":
				$itemName = 'value';
				break;           
		}
	
		$itemKeys = array_column($itemsLastSync, $itemName);
	
		$added = array();
	
		foreach ($items as $item){
			if (!$this->in_arrayi($item[$itemName], $itemKeys)){
				$added[] = $item[$itemName];
			}
		}
		return $added;
	}

	private function in_arrayi($needle, $haystack) {
		return in_array(strtolower($needle), array_map('strtolower', $haystack));
	}



    public function GetConfigurationForm(): string
    {
        return json_encode(
            [
                'elements' => $this->FormElements(),
                'actions'  => $this->FormActions(),
                'status'   => $this->FormStatus()]
        );
    }

    private function FormElements(): array
    {


		// Panel:Alexa shopping list synchronisation
		$panelItems = array();

		$elements[] = [
            'type'    => 'SelectInstance',
            'name'    => 'InstanceID1',
            'caption' => 'Shopping list 1',
			'width'	  => '600px',
			'validModules' => $this->supportedModules
        ];

		$elements[] = [
            'type'    => 'SelectInstance',
            'name'    => 'InstanceID2',
            'caption' => 'Shopping list 2',
			'width'	  => '600px',
			'validModules' => $this->supportedModules
        ];

		$elements[] = [
            'type'    => 'Select',
            'name'    => 'SyncMode',
            'caption' => "Synchronisation mode",
			'width'	  => '600px',
            'options' => [
				['caption' => 'Transfer: List 1 → List 2 (Add entries from list 1 to list 2 and delete them from list 1)', 'value' => 1],
				['caption' => 'Sync: List 1 ↔ List 2 (Add and delete changed entries on both lists to keep them in sync)', 'value' => 2]
			]
        ];

		$elements[] = [
            'type'    => 'Select',
            'name'    => 'SyncInterval',
            'caption' => "Synchronisation interval",
			'width'	  => '600px',
            'options' => [
				['caption' => 'disabled', 'value' => 0],
				['caption' => '5 minutes', 'value' => 5*60],
				['caption' => '15 minutes', 'value' => 15*60],
				['caption' => '60 minutes', 'value' => 60*60],
			]
        ];

        return $elements;
    }

    /**
     * return form actions by token.
     *
     * @return array
     */
    private function FormActions(): array
    {

		$elements[] = [
            'type'    => 'Button',
            'caption' => 'Synchronize',
			'enabled' => ($this->ReadPropertyInteger('InstanceID1')>1 && $this->ReadPropertyInteger('InstanceID2')>1),
            'onClick' => 'IPS_RequestAction($id, "Sync", "");', 
        ];

        return $elements;
    }

    /**
     * return from status.
     *
     * @return array
     */
    private function FormStatus(): array
    {
        $form = [
            [
                'code' => 201,
                'icon' => 'error',
                'caption' => 'List instance does not exist'
			]
        ];

        return $form;
    }
}
