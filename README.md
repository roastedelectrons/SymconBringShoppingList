# SymconBringShoppingList

Folgende Module beinhaltet das SymconBringShoppingList Repository:

- __Bring Shopping List__ ([Dokumentation](Bring%20Shopping%20List))  
	Modul zum Anzeigen und Bearbeiten von Bring Einkauslisten.

- __Shopping List Sync__ ([Dokumentation](ShoppingListSync))  
	Modul zur Synchronisierung von Bring- und Alexa-Einkaufslisten (Echo Remote Modul erforderlich).


## Synchronisierung von Alexa und Bring! Einkaufslisten
1. Eine ``Alexa Einkaufs- und ToDo-Liste``-Instanz aus dem EchoRemote Modul anlegen und die gewünschte Liste in der Instanzkonfiguration auswählen.
2. Eine ``Bring! Einkaufsliste``-Instanz aus diesem Modul anlegen und Benutzterdaten und Liste in der Instanzkonfiguration einstellen.
3. Eine ``Einkaufslisten Synchronisation für Bring! und Alexa``-Instanz aus diesem Modul anlegen und konfigurieren:
	* Erstellte Alexa Einkaufslisten Instanz auswählen
	* Erstellte Bring Einkaufslisten Instanz auswählen
	* Synchronisationsmodus auswählen

## Quellen
1. Python bring-api Version 2 by runningtoy https://github.com/runningtoy/bring-api/
2. PHP bring-api by helvete003 https://github.com/helvete003/bring-api