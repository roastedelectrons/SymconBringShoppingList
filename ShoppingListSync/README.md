# Shopping List Sync
Modul zur Synchronisierung von Einkaufslisten von Bring! und Alexa.
Untersützte Listen Module: 
*  Bring Shopping List (aus diesem Modul)
*  Alexa List (Echo Remote Modul)

## Inhaltsverzeichnis
1. [Funktionsumfang](#funktionsumfang)
2. [Konfiguration der Instanz](#konfiguration-der-instanz)
3. [Statusvariablen und Profile](#statusvariablen-und-profile)
4. [PHP-Befehlsreferenz](#php-befehlsreferenz)

## Funktionsumfang

## Konfiguration der Instanz

|Eigenschaft| Typ| Beschreibung| Wert |
|-----| -----| -----| ----- |
|InstanceID1 | SelectInstance | Shopping list 1 | |
|InstanceID2 | SelectInstance | Shopping list 2 | |
|SyncMode | Select | Synchronisierungsmodus | `Transfer: List 1 → List 2 (Add entries from list 1 to list 2 and delete them from list 1)`, `Sync: List 1 ↔ List 2 (Add and delete changed entries on both lists to keep them in sync)`|
|SyncInterval | Select | Synchronisierungsintervall | `disabled`, `5 minutes`, `15 minutes`, `60 minutes`|

## Statusvariablen und Profile

|Ident| Typ| Profil| Beschreibung |
|-----| -----| -----| ----- |

## PHP-Befehlsreferenz

### Sync
```php
ShoppingListSync_Sync( int $InstanceID );
```
|Parameter| Typ| Beschreibung |
|-----| -----| ----- |
|$InstanceID |int |ID der Shopping List Sync-Instanz |
