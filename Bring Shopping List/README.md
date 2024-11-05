# Bring Shopping List
(Beschreibung)

## Inhaltsverzeichnis
1. [Funktionsumfang](#funktionsumfang)
2. [Konfiguration der Instanz](#konfiguration-der-instanz)
3. [Statusvariablen und Profile](#statusvariablen-und-profile)
4. [PHP-Befehlsreferenz](#php-befehlsreferenz)

## Funktionsumfang
* Einträge hinzufügen, abhaken und löschen
* Darstellung und Bearbeitung von Listen in Tile-Visu
* Synchronisierung mit Alexa Listen (Echo Remote Modul erforderlich)

## Konfiguration der Instanz

|Eigenschaft| Typ| Beschreibung| Wert |
|-----| -----| -----| ----- |
|Email | ValidationTextBox | Email | |
|Password | PasswordTextBox | Password | |
|ListID | Select | Liste | `Zuhause`, `Eigene Listen...`|
|UpdateInterval | NumberSpinner | Aktualisierungsintervall in Minuten | |

### Visualisierung
|Eigenschaft| Typ| Beschreibung| Wert |
|-----| -----| -----| ----- |
|ShowCompletedItems | CheckBox | Zeige erledigte Einträge | `false`|
|DeleteCompletedItems | CheckBox | Lösche erledigte Einträge von Liste | `false`|

### Alexa Synchronisation
|Eigenschaft| Typ| Beschreibung| Wert |
|-----| -----| -----| ----- |
|AlexaListInstance | SelectInstance | AlexaList Instanz | |
|SyncMode | Select | Synchronisierungsmodus | `Alexa → Bring! (Add entries to Bring!List and delete them from AlexaList)`|
|SyncInterval | Select | Synchronisierungsintervall | `disabled`, `5 minutes`, `15 minutes`, `60 minutes`|

## Statusvariablen und Profile

|Ident| Typ| Profil| Beschreibung |
|-----| -----| -----| ----- |
|AddItem |string | |Add Item |
|List |string |~TextBox |List |

## PHP-Befehlsreferenz

### AddItem
```php
BringList_AddItem( int $InstanceID, string $text, string $specification );
```
|Parameter| Typ| Beschreibung |
|-----| -----| ----- |
|$InstanceID |int |ID der Bring Shopping List-Instanz |
|$text |string | |
|$specification |string | |

### CheckItem
```php
BringList_CheckItem( int $InstanceID, string $itemText );
```
|Parameter| Typ| Beschreibung |
|-----| -----| ----- |
|$InstanceID |int |ID der Bring Shopping List-Instanz |
|$itemText |string | |

### DeleteItem
```php
BringList_DeleteItem( int $InstanceID, string $itemText );
```
|Parameter| Typ| Beschreibung |
|-----| -----| ----- |
|$InstanceID |int |ID der Bring Shopping List-Instanz |
|$itemText |string | |

### GetListItems
```php
BringList_GetListItems( int $InstanceID, bool $includeCompletedItems );
```
|Parameter| Typ| Beschreibung |
|-----| -----| ----- |
|$InstanceID |int |ID der Bring Shopping List-Instanz |
|$includeCompletedItems |bool | |

### GetLists
```php
BringList_GetLists( int $InstanceID, bool $cached );
```
|Parameter| Typ| Beschreibung |
|-----| -----| ----- |
|$InstanceID |int |ID der Bring Shopping List-Instanz |
|$cached |bool | |

### UncheckItem
```php
BringList_UncheckItem( int $InstanceID, string $itemText );
```
|Parameter| Typ| Beschreibung |
|-----| -----| ----- |
|$InstanceID |int |ID der Bring Shopping List-Instanz |
|$itemText |string | |

### Update
```php
BringList_Update( int $InstanceID );
```
|Parameter| Typ| Beschreibung |
|-----| -----| ----- |
|$InstanceID |int |ID der Bring Shopping List-Instanz |