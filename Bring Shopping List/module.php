<?php

declare(strict_types=1);

class BringShoppingList extends IPSModule
{

	const GET_REQUEST = 'get';
	const POST_REQUEST = 'post';
	const PUT_REQUEST = 'put';

	private $bringRestURL = "https://api.getbring.com/rest/v2/";
	private $httpStatus = -1;

    public function Create()
    {
        //Never delete this line!
        parent::Create();

		$this->RegisterPropertyString('Email', '');
		$this->RegisterPropertyString('Password', '');
        $this->RegisterPropertyString('ListID', '');
        $this->RegisterPropertyBoolean('ShowCompletedItems', false);
        $this->RegisterPropertyBoolean('DeleteCompletedItems', false);
        $this->RegisterPropertyInteger('UpdateInterval', 60);
		$this->RegisterPropertyInteger('AlexaListInstance', 0);
		$this->RegisterPropertyInteger('SyncMode', 1);
		$this->RegisterPropertyInteger('SyncInterval', 0);

		$this->RegisterAttributeString('UUID', '');
		$this->RegisterAttributeString('AccessToken', '');
		$this->RegisterAttributeString('RefreshToken', '');
		$this->RegisterAttributeInteger('TokenExpires', 0);
		$this->RegisterAttributeString('ListUUID', '');
        $this->RegisterAttributeString('Lists', '');
        $this->RegisterAttributeString('ListItems', '');

        $this->RegisterTimer('Update', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "Update", "");');
		$this->RegisterTimer('RefreshToken', 0, 'IPS_RequestAction('.$this->InstanceID.', "Login", "");');  
		$this->RegisterTimer('Sync', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "Sync", "");');

		if (IPS_GetKernelVersion() >= 7.1)
			$this->SetVisualizationType(1);

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

		$alexaListInstance = $this->ReadPropertyInteger('AlexaListInstance');
		if ($alexaListInstance > 1){
			$this->RegisterReference($alexaListInstance);
			$this->SetTimerInterval('Sync', $this->ReadPropertyInteger('SyncInterval') * 1000);
		} else {
			$referenceList = $this->GetReferenceList();
			foreach ($referenceList as $reference) {
                $this->UnregisterReference($reference);
            }
			$this->SetTimerInterval('Sync', 0);
		}
			

        $this->MaintainVariable('AddItem', $this->Translate('Add Item'), 3, '', 1, true );
        $this->MaintainAction('AddItem', true);
        $this->MaintainVariable('List', $this->Translate('List'), 3, '~TextBox', 1, true );

        $this->SetTimerInterval('Update', $this->ReadPropertyInteger('UpdateInterval') * 60 *1000);
		

        if ($this->GetStatus() != 200 || $this->ReadAttributeString('AccessToken') == '')
			$this->Login();

		if ($this->GetStatus() == IS_ACTIVE)
			$this->Update();
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
			case 'Login':
				$this->Login();
				break;

            case 'Update':
                $this->Update();
                break;
			
			case 'Sync':
				$this->Sync();
				break;

			// Configuration form
            case 'UpdateLists':
                $this->GetLists();
                $this->UpdateFormField('ListID', 'options', json_encode($this->GetListIDsForSelect() )) ;
                break;

			// Variables
            case 'AddItem':
                $this->SetValue($ident, $value);
                $this->AddItem($value);
                $this->Update();
                $this->SetValue($ident, '');
                break;

			// Visu
            case 'VisuGetList':
                $items = $this->ReadAttributeString('ListItems',);
                $this->UpdateVisualizationValue($items);
                break;

            case 'VisuAddItem':
                $this->AddItem($value);
                $this->Update();
                break;

            case 'VisuCheckItem':
                if ($this->ReadPropertyBoolean('DeleteCompletedItems')){
                    $this->DeleteItem( $value );
                } else {
                    $this->CheckItem( $value );
                }
                $this->Update();
                break;

            case 'VisuUncheckItem':
                $this->UncheckItem( $value );
                $this->Update();
                break;

        }

    }

    public function GetVisualizationTile() {

        // Füge statisches HTML aus Datei hinzu
        $module = file_get_contents(__DIR__ . '/module.html');

        return $module;
    }

    private function UpdateListVariable( array $listItems )
    {
        $string = '';

        foreach($listItems as $item){
            $symbol = "☐";
            if ($item['completed'])
            	$symbol = "☑";
            $string .= $symbol. "    " .$item['name']."\n\r";
        }
        
        $this->SetValue('List', $string);
    }

    public function Update()
    {
        if ($this->ReadPropertyBoolean('ShowCompletedItems')){
            $items = $this->GetListItems(true);
        } else {
            $items = $this->GetListItems(false);
        }
        
        
        $this->WriteAttributeString('ListItems', json_encode($items));

        $this->UpdateListVariable($items);
        if (IPS_GetKernelVersion() >= 7.1){
            $this->UpdateVisualizationValue( json_encode($items) );
        }
    }

	private function Sync()
	{
		$semaphore = 'BringShoppingList.Sync.'.$this->ReadPropertyString('ListID');

		if (IPS_SemaphoreEnter($semaphore, 1000) === false)
			return false;

		$alexaListInstance = $this->ReadPropertyInteger('AlexaListInstance');

		if (!IPS_InstanceExists($alexaListInstance))
			return false;

		$this->SendDebug( __FUNCTION__, 'Start', 0);

		if ($this->ReadPropertyInteger('SyncMode') == 1){

			$alexaListItems = ALEXALIST_GetListItems($alexaListInstance, false);

			foreach($alexaListItems as $item){
				$result = $this->AddItem($item['value'], '');
				if ($result){
					ALEXALIST_DeleteItemByID($alexaListInstance, $item['id']);
				}
			}
	
			if (count($alexaListItems) > 0){
				$this->Update();
				ALEXALIST_Update($alexaListInstance);
				$this->LogMessage('Synchronized '.count($alexaListItems).' item(s) from Alexa to Bring shopping list: '. implode(', ', array_column($alexaListItems, 'value') ) , KL_MESSAGE);
				$this->SendDebug( __FUNCTION__, 'Synchronized items: '. json_encode($alexaListItems), 0);
			} else{
				$this->SendDebug( __FUNCTION__, 'No items to sync', 0);
			}

		}

		$this->SendDebug( __FUNCTION__, 'End', 0);
		IPS_SemaphoreLeave($semaphore);

	}

	private function Login()
	{
		$email = $this->ReadPropertyString('Email');
		$password = $this->ReadPropertyString('Password');

		if ($email == '' || $password == ''){
			$this->SetStatus(201);
			return false;
		}

		$options['email'] = $email;
		$options['password'] = $password;

		$result = $this->request(self::POST_REQUEST,"bringauth", http_build_query($options), $this->getAuthHeader() );


		if ($this->httpStatus == 401){
			$this->WriteAttributeString('AccessToken', '');
			$this->WriteAttributeString('RefreshToken', '');
			$this->WriteAttributeString('UUID', '');
			return false;
		}

		if ($this->httpStatus == 200){
			$login = json_decode( $result, true);

			if (isset($login['access_token'])){
				$this->WriteAttributeString('UUID', $login['uuid']);
				$this->WriteAttributeString('ListUUID', $login['bringListUUID']);
				$this->WriteAttributeString('AccessToken', $login['access_token']);
				$this->WriteAttributeString('RefreshToken', $login['refresh_token']);
				$this->WriteAttributeInteger('TokenExpires', time()+ $login['expires_in']);
				$this->SetTimerInterval('RefreshToken', ( $login['expires_in'] - 3600)*1000);

				if ($this->GetStatus() != IS_ACTIVE)
					$this->SetStatus(IS_ACTIVE);

				return true;
			}
		}
		
		return false;
	}


    public function AddItem( string $itemText, string $specificationText = "" )
    {

        $item['name']          = $itemText;
		$item['specification'] = $specificationText;

        $result = $this->addListItem( $item );

        return $result;
    }



    public function CheckItem( string $itemText )
    {
		$item = $this->getListItemByName( $itemText );

		if ($item == [])
			return false;

		$item['completed'] = true;

        $result = $this->updateListItem($item);

        return $result;
    }



    public function UncheckItem( string $itemText )
    {
		$item = $this->getListItemByName( $itemText );

		if ($item == [])
			return false;

		$item['completed'] = false;

        $result = $this->updateListItem($item);

        return $result;
    }

    public function DeleteItem( string $itemText )
    {

        return $this->deleteListItem($itemText);

    }


    public function GetListItems(bool $includeCompletedItems = false): ?array
    {

		$result = $this->request(self::GET_REQUEST,"bringlists/".$this->ReadPropertyString('ListID'),"");

        if ($this->httpStatus != 200) {
			return [];
        }

		$list = json_decode($result, true);

		$items = array();

		$list['purchase']  = array_reverse($list['purchase'] );

		foreach($list['purchase'] as $item){
			$item['completed'] = false;
			$item['id'] = $item['name'];
			$item['value'] = $item['name'];
			$items[] = $item;
		}

		$list['recently']  = array_reverse($list['recently'] );

		if ($includeCompletedItems){
			foreach($list['recently'] as $item){
				$item['completed'] = true;
				$item['id'] = $item['name'];
				$item['value'] = $item['name'];
				$items[] = $item;
			}
		}

        return $items;
    }

    private function getListItemByName( $itemText )
    {
        $items = json_decode($this->ReadAttributeString('ListItems'), true);

        foreach ($items as $item){
            if ($item['name'] == $itemText){
                return $item;
            }
        }

        return [];
    }

    private function addListItem(array $itemArray)
    {
		$options['purchase'] = $itemArray['name'];
		$options['recently'] = '';
		$options['specification'] = $itemArray['specification'];
		$options['remove'] = '';
		$options['sender'] = 'null';

		$result = $this->request(self::PUT_REQUEST,"bringlists/".$this->ReadPropertyString('ListID'), http_build_query($options));

        if ($this->httpStatus === 204) {
            return true;
        }

        return false;
    }

    private function updateListItem(array $itemArray)
    {
		if ($itemArray['completed'] == true){
			$options['purchase'] = '';
			$options['recently'] = $itemArray['name'];
			$options['specification'] = $itemArray['specification'];
			$options['remove'] = '';
			$options['sender'] = 'null';
		} else {
			$options['purchase'] = $itemArray['name'];
			$options['recently'] = '';
			$options['specification'] = $itemArray['specification'];
			$options['remove'] = '';
			$options['sender'] = 'null';			
		}

		$result = $this->request(self::PUT_REQUEST,"bringlists/".$this->ReadPropertyString('ListID'), http_build_query($options));

        if ($this->httpStatus === 204) {
            return true;
        }

        return false;
    }

    private function deleteListItem( string $itemName)
    {
		$options['purchase'] = '';
		$options['recently'] = '';
		$options['specification'] = '';
		$options['remove'] = $itemName;
		$options['sender'] = 'null';

		$result = $this->request(self::PUT_REQUEST,"bringlists/".$this->ReadPropertyString('ListID'), http_build_query($options));

        if ($this->httpStatus === 204) {
            return true;
        }

        return false;
    }


    public function GetLists( bool $cached = false)
    {

        if ($cached == true) {

            $items = json_decode( $this->ReadAttributeString('Lists'), true);

            if (is_array($items)){
                return $items;   
			} else {
				return [];
			}
  

        }

		$result = $this->request(self::GET_REQUEST,"bringusers/".$this->ReadAttributeString('UUID')."/lists", "");

		if ($this->httpStatus != 200)
			return [];

        $lists = json_decode($result, true);

        if (!isset($lists['lists'])) {
            return [];
        }

        $this->WriteAttributeString('Lists', json_encode($lists['lists']));

        return $lists;
    }

    private function GetListIDsForSelect()
    {
        
        $items = $this->GetLists(true);

        $selectOptions = array();

        foreach( $items as $item){

			$option = [
				'caption' => $item['name'],
				'value'   => $item['listUuid']
			];                
            
            $selectOptions[] = $option;
        }

		if ($selectOptions == array()){
			$selectOptions[] = [
				'caption' => 'no lists available',
				'value'   => ''
			];  
		}

        return $selectOptions;
    }


	/**
	 *   Handles the request to the server
	*
	*   @param const string $type   The HTTP request type.
	*   @param string $request      contains the request URL
	*   @param string $parameter    The parameters we send with the request
	*   @param bool $customHeader   True if you want to send the custom header (That is necessary because it sends the API-KEY) with the request
	*   @return The answer string from the server
	*/
	private function request($type = self::GET_REQUEST,$request, $parameter, array $customHeader = null)
	{
		$ch = curl_init();

		if ($customHeader === null){
			$header = $this->getHeader();
		} else {
			$header = $customHeader;
		}


		switch($type) {
			case self::GET_REQUEST:
				$url = $this->bringRestURL.$request.$parameter;
				curl_setopt($ch, CURLOPT_URL, $url);
			break;

			case self::POST_REQUEST:
				$url = $this->bringRestURL.$request;
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS,$parameter);
			break;

			case self::PUT_REQUEST:
				$url = $this->bringRestURL.$request;
				$fh = tmpfile();
				fwrite($fh, $parameter);
				fseek($fh, 0);
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_PUT, true);
				curl_setopt($ch, CURLOPT_INFILE, $fh);
				curl_setopt($ch, CURLOPT_INFILESIZE, strlen($parameter));
				$header[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
			break;
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);  
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); 	
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

		$result = curl_exec ($ch);
		$this->httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close ($ch);

		$this->SendDebug( __FUNCTION__, 'URL: '. $url , 0);
		$this->SendDebug( __FUNCTION__, 'HEADER: '. json_encode($header) , 0);
		$this->SendDebug( __FUNCTION__, 'HTTP-Status: '. $this->httpStatus , 0);
		$this->SendDebug( __FUNCTION__, $result , 0);


		if ($this->httpStatus == 401){
			$error = json_decode($result, true);
			switch ($error['error']){
				case 'invalid_token':
					if ($this->GetStatus() != 201){
						$this->SetStatus(202);
					}
					break;

				case 'unauthorized':
					$this->SetStatus(201);
					break;
			}
			trigger_error($error['message']);
		}

		return $result;
	}


	private function getHeader()
	{
		$header = [
			'X-BRING-API-KEY: cof4Nc6D8saplXjE3h3HXqHH8m7VU2i1Gs0g85Sp',
			'X-BRING-CLIENT: webApp',
			'X-BRING-CLIENT-SOURCE: webApp',
			'X-BRING-COUNTRY: de',
			'X-BRING-USER-UUID: '. $this->ReadAttributeString('UUID'),
			'Authorization: Bearer '. $this->ReadAttributeString('AccessToken')
		];

		return $header;
	}

	private function getAuthHeader()
	{
		$header = [
			'X-BRING-API-KEY: cof4Nc6D8saplXjE3h3HXqHH8m7VU2i1Gs0g85Sp',
			'X-BRING-CLIENT: webApp',
			'X-BRING-CLIENT-SOURCE: webApp',
			'X-BRING-COUNTRY:de',
			'Content-Type: application/x-www-form-urlencoded; charset=UTF-8'
		];
		return $header;
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

		$elements[] = [
            'type'    => 'ValidationTextBox',
			'name'    => 'Email',
            'caption' => 'Email'
        ];

		$elements[] = [
            'type'    => 'PasswordTextBox',
			'name'    => 'Password',
            'caption' => 'Password'
        ];

        $rowItems[] = [
            'type'    => 'Select',
            'name'    => 'ListID',
            'caption' => "List",
            'options' => $this->GetListIDsForSelect()
        ];

        $rowItems[] = [
            'type'    => 'Button',
            'caption' => 'Reload lists',
            'onClick' => 'IPS_RequestAction($id, "UpdateLists", "");', 
        ];

        $elements[] = [
            'type'    => 'RowLayout',
            'items'    => $rowItems
        ];


		$elements[] = [
            'type'    => 'NumberSpinner',
            'name'    => 'UpdateInterval',
            'caption' => 'Update interval',
            'suffix' => 'minutes',
            'minimum' => 0
        ];


		// Panel: Visualisation settings
		$panelItems = array();

		$panelItems[] = [
			'type'    => 'CheckBox',
			'name'    => 'ShowCompletedItems',
			'caption' => "Show completed items"
		];

		$panelItems[] = [
			'type'    => 'CheckBox',
			'name'    => 'DeleteCompletedItems',
			'caption' => "Delete completed items from list"
		];

		$elements[] = [
            'type'    => 'ExpansionPanel',
			'caption' => 'Visualisation settings',
            'items'    => $panelItems
        ];

		// Panel:Alexa shopping list synchronisation
		$panelItems = array();

		$panelItems[] = [
            'type'    => 'SelectInstance',
            'name'    => 'AlexaListInstance',
            'caption' => 'AlexaList Instance',
			'width'	  => '600px',
			'validModules' => ['{7129178B-E633-238A-0851-2F1B5A09805E}']
        ];

		$panelItems[] = [
            'type'    => 'Select',
            'name'    => 'SyncMode',
            'caption' => "Synchronisation mode",
			'width'	  => '600px',
            'options' => [
				['caption' => 'Alexa → Bring! (Add entries to Bring!List and delete them from AlexaList)', 'value' => 1]
			]
        ];

		$panelItems[] = [
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

        $elements[] = [
            'type'    => 'ExpansionPanel',
			'caption' => 'Alexa synchronisation (Echo Remote module required)',
            'items'    => $panelItems
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
            'caption' => 'Refresh',
            'onClick' => 'IPS_RequestAction($id, "Update", "");', 
        ];

		$elements[] = [
            'type'    => 'Button',
            'caption' => 'Synchronize',
			'enabled' => ($this->ReadPropertyInteger('AlexaListInstance')>1),
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
                'caption' => 'Incorrect login data'
			],
			[
                'code' => 202,
                'icon' => 'error',
                'caption' => 'Invalid access token. Login again!'
            ]
        ];

        return $form;
    }
}
