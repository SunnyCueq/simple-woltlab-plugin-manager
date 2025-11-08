# Beispiel-Plugin - Simple Package

Dies ist ein einfaches Beispiel-Plugin basierend auf der [WoltLab Getting Started Dokumentation](https://docs.woltlab.com/6.0/getting-started/).

## Struktur

```
example-plugin/
├── files/
│   └── lib/
│       └── page/
│           └── TestPage.class.php    # PHP-Klasse für die Seite
├── templates/
│   └── test.tpl                       # Template für die Seite
├── package.xml                       # Plugin-Manifest
├── page.xml                          # Seiten-Definition
└── README.md                         # Diese Datei
```

## Was macht dieses Plugin?

Das Plugin erstellt eine einfache Test-Seite, die "Hello World!" anzeigt. Die Seite ist unter `index.php?test/` erreichbar.

Optional kann ein Parameter übergeben werden: `index.php?test/&greet=You` zeigt dann "Hello You!" an.

## Verwendung

1. **TAR-Archive erstellen:**
   ```bash
   cd files && tar -cf ../files.tar * && cd ..
   cd templates && tar -cf ../templates.tar * && cd ..
   ```

2. **Package erstellen:**
   ```bash
   tar -czf com.example.test.tar.gz \
     package.xml \
     page.xml \
     files.tar \
     templates.tar
   ```

3. **Installation:**
   - Im ACP: `Configuration > Packages > Install Package`
   - Package hochladen und installieren

4. **Testen:**
   - Öffnen Sie: `https://ihre-domain.de/index.php?test/`
   - Oder mit Parameter: `https://ihre-domain.de/index.php?test/&greet=You`

## Anpassungen

- **Seiten-URL ändern:** Bearbeiten Sie `page.xml` und ändern Sie den Identifier
- **Template anpassen:** Bearbeiten Sie `templates/test.tpl`
- **PHP-Logik erweitern:** Bearbeiten Sie `files/lib/page/TestPage.class.php`

## Weitere Informationen

- [WoltLab Getting Started](https://docs.woltlab.com/6.0/getting-started/)
- [WoltLab PHP API Dokumentation](https://docs.woltlab.com/6.0/api/)
- [WoltLab Template Dokumentation](https://docs.woltlab.com/6.0/templates/)

