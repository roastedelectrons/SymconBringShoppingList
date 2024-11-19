# Bring Shopping List
Modul zum Anzeigen und Bearbeiten von Bring Einkauslisten.

## Inhaltsverzeichnis
1. [Funktionsumfang](#funktionsumfang)
2. [Konfiguration der Instanz](#konfiguration-der-instanz)
3. [Statusvariablen und Profile](#statusvariablen-und-profile)
4. [PHP-Befehlsreferenz](#php-befehlsreferenz)

## Funktionsumfang
* Anzeige von Einkaufslisten (WebFront und Tile-Visu)
* Hinzufügen und Entfernen von Einträgen

## Konfiguration der Instanz

|Eigenschaft| Typ| Beschreibung| Wert |
|-----| -----| -----| ----- |
|Email | ValidationTextBox | Email | |
|Password | PasswordTextBox | Password | |

|Eigenschaft| Typ| Beschreibung| Wert |
|-----| -----| -----| ----- |
|ListID | Select | Liste | `Select list`, `Zuhause`, `Test`|
|ShowCompletedItems | CheckBox | Zeige erledigte Einträge | `false`|
|DeleteCompletedItems | CheckBox | Lösche erledigte Einträge von Liste | `false`|
|UpdateInterval | NumberSpinner | Aktualisierungsintervall | |

## Statusvariablen und Profile

|Ident| Typ| Profil| Beschreibung |
|-----| -----| -----| ----- |
|List |string |~TextBox |List |
|AddItem |string | |Add Item |

## PHP-Befehlsreferenz

### AddItem
```php
BringList_AddItem( int $InstanceID, string $itemText, string $specificationText );
```
|Parameter| Typ| Beschreibung |
|-----| -----| ----- |
|$InstanceID |int |ID der Bring Shopping List-Instanz |
|$itemText |string | |
|$specificationText |string | |

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

### GetItems
```php
BringList_GetItems( int $InstanceID, bool $includeCompletedItems );
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