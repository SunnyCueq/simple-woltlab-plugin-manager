# Firefox-Fix für woltlab.ddev.site

## Problem: Funktioniert nur im privaten Tab

Das bedeutet, dass Firefox gespeicherte Daten (Cache, Cookies) hat, die das Problem verursachen.

## Lösung 1: Daten für diese Domain löschen (Empfohlen)

1. Öffne Firefox
2. Drücke **Strg+Shift+Del** (oder **Cmd+Shift+Del** auf Mac)
3. Wähle:
   - **Zeitraum**: "Alles"
   - **Cookies und Site-Daten**: ✅
   - **Zwischengespeicherte Web-Inhalte**: ✅
4. Klicke auf **Jetzt löschen**

## Lösung 2: Nur für woltlab.ddev.site löschen

1. Öffne `https://woltlab.ddev.site` in Firefox
2. Klicke auf das **Schloss-Symbol** in der Adressleiste
3. Klicke auf **Cookies und Website-Daten**
4. Klicke auf **Entfernen**
5. Seite neu laden (F5)

## Lösung 3: Service Worker löschen (falls vorhanden)

1. Öffne `about:serviceworkers` in Firefox
2. Suche nach `woltlab.ddev.site`
3. Klicke auf **Abmelden** oder **Entfernen**

## Lösung 4: Hard Refresh

1. Öffne `https://woltlab.ddev.site`
2. Drücke **Strg+Shift+R** (oder **Cmd+Shift+R** auf Mac)
3. Das leert den Cache für diese Seite

## Lösung 5: Firefox komplett zurücksetzen (nur wenn nichts anderes hilft)

1. Öffne `about:support` in Firefox
2. Klicke auf **Firefox zurücksetzen...**
3. **ACHTUNG**: Das löscht alle Einstellungen und Add-ons!
