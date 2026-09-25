# Produktkarte des Moduls Next Order Discount für PrestaShop

Fertige deutschsprachige Texte für die Produktkarte, die Produktwebsite und die Vertriebsunterlagen. Die Formulierungen beschreiben den tatsächlichen Funktionsumfang der Version 1.0.0, ohne garantierte Umsatzsteigerungen zu versprechen.

---

## 1. Modulname

### Empfohlene Variante

**Next Order Discount: persönlicher Gutschein für die nächste Bestellung**

Der Name erklärt sofort die Funktionsweise des Moduls, betont den persönlichen Charakter des Angebots und enthält den wichtigsten Suchbegriff „Gutschein für die nächste Bestellung“.

### Variante mit Fokus auf das Geschäftsziel

**Persönlicher Gutschein für die nächste Bestellung — holen Sie Ihre Kunden zurück**

### Variante mit Fokus auf die Automatisierung

**Automatischer persönlicher Gutschein nach dem Kauf**

---

## 2. Kurzbeschreibung

**Schaffen Sie schon mit der abgeschlossenen Bestellung einen Anlass zum Wiederkauf: Vergeben Sie automatisch persönliche Gutscheine, versenden Sie E-Mails und Erinnerungen und verfolgen Sie das Ergebnis in einem einzigen Funnel.**

---

## 3. Vollständige Beschreibung

# Holen Sie Ihre Kunden mit einem persönlichen Gutschein zurück

Senden Sie Ihren Kundinnen und Kunden nach Abschluss der Bestellung einen persönlichen Gutschein für den nächsten Einkauf — als prozentualen Rabatt, festen Betrag oder kostenlosen Versand.

**Next Order Discount** erstellt einen persönlichen Gutschein, sobald eine Bestellung die festgelegten Bedingungen erfüllt, und sendet dem Kunden eine E-Mail mit dem Code und den Einlösebedingungen. Bleibt der Gutschein ungenutzt, kann das Modul bis zu zwei automatische Erinnerungen verschicken. Vergaberegeln, E-Mails und Statistiken sind im PrestaShop-Back-Office verfügbar.

## So funktioniert es

1. Sie erstellen eine Regel und legen fest, welche Bestellungen an der Kampagne teilnehmen.
2. Sobald eine Bestellung alle Bedingungen erfüllt und den gewünschten Status erreicht, erstellt das Modul einen persönlichen Gutschein und sendet dem Kunden sofort die E-Mail.
3. Das Modul versendet die geplanten Erinnerungen automatisch, wiederholt fehlgeschlagene Sendungen und aktualisiert die Gutscheinstatus.
4. Im Dashboard sehen Sie den Weg der Gutscheine von der Erstellung bis zur Einlösung.

Nach der Ersteinrichtung läuft die Kampagne von selbst, die Ergebnisse bleiben unter Ihrer Kontrolle.

## Verschiedene Angebote für verschiedene Ziele

Statt eines pauschalen Rabatts für alle erstellen Sie eigene Angebote für Neukunden, Stammkunden, große Bestellungen, bestimmte Länder oder ausgewählte Produktkategorien. Jede Regel bestimmt, wer einen Gutschein erhält, welchen Vorteil er bekommt und wie lange er ihn nutzen kann.

Für jede Regel stehen drei Vorteilsarten zur Verfügung:

- prozentualer Rabatt;
- fester Rabattbetrag;
- kostenloser Versand.

Gültigkeitsdauer des Gutscheins und Mindestbetrag der nächsten Bestellung werden separat festgelegt.

Beispielszenarien:

- **10 % nach der ersten Bestellung** — einen Neukunden behutsam zum zweiten Kauf bewegen;
- **15 € Rabatt ab einer Bestellung von 120 €** — Kunden mit hohem Warenkorbwert belohnen;
- **kostenloser Versand für Stammkunden** — einer bestimmten Gruppe ein Privileg bieten;
- **saisonaler Gutschein für ausgewählte Marken oder Kategorien** — eine gezielte Kampagne unterstützen, ohne den gesamten Katalog zu rabattieren.

## Gutscheine nur an die passenden Kunden vergeben

Alle Bedingungen einer Regel werden gleichzeitig geprüft. Konfigurierbar sind:

- die Bestellstatus, bei denen ein Gutschein erstellt wird;
- Mindest- und Höchstsumme der auslösenden Bestellung;
- der Aktionszeitraum der Kampagne;
- die laufende Bestellnummer des Kunden — zum Beispiel nur die erste Bestellung oder ab der dritten;
- Kundengruppen;
- Länder;
- Währungen;
- Produktkategorien;
- Marken.

Für Listenbedingungen stehen die Modi „Alle“, „Nur die ausgewählten“ und „Alle außer den ausgewählten“ zur Verfügung.

Die Regeln werden nach Priorität ausgewertet. Mit der Option zum Stoppen der weiteren Auswertung entscheiden Sie, ob ein Kunde nur einen Gutschein erhält oder mehrere Gutscheine aus verschiedenen zutreffenden Regeln.

## Jede E-Mail auf die Kampagne abstimmen

Jede Regel hat ihren eigenen E-Mail-Satz:

- die Haupt-E-Mail mit dem Gutschein;
- die erste Erinnerung;
- die zweite Erinnerung.

Betreff und HTML-Inhalt lassen sich für jede Sprache des Shops separat einstellen. Platzhalter setzen Code und Rabatthöhe, Ablaufdatum, Mindestbestellwert, Kundenname, Shopname und weitere Daten ein.

Öffnen Sie vor dem Start der Kampagne die Vorschau und senden Sie sich eine Testkopie an die eigene Adresse.

## Aufmerksamkeit auf ungenutzte Gutscheine zurücklenken

Richten Sie eine oder zwei Erinnerungen ein und wählen Sie, wie das Datum berechnet wird:

- eine festgelegte Anzahl Tage nach dem Versand der Haupt-E-Mail;
- eine festgelegte Anzahl Tage vor Ablauf des Gutscheins.

Wurde der Gutschein bereits eingelöst, ist er abgelaufen oder storniert, werden keine Erinnerungen mehr versendet.

## Das Format des Gutscheincodes steuern

Für jede Regel lässt sich einstellen:

- die Länge des zufälligen Code-Teils;
- der Zeichensatz — Buchstaben, Ziffern oder eine Kombination;
- eine Vorlage mit der Variablen `%key%`.

Die Vorlage `RETURN-%key%` kann beispielsweise den Code `RETURN-AB12CD8X` erzeugen.

## Ergebnisse in einem einzigen Funnel kontrollieren

Das Dashboard zeigt die Anzahl der Gutscheine je Stufe:

**erstellt → versendet → erinnert → eingelöst → abgelaufen → storniert**.

Dort wird auch die Conversion von erstellten zu eingelösten Gutscheinen berechnet und der Zustand der Warteschlange für Wiederholungen und Erinnerungen angezeigt. So bewerten Sie die tatsächliche Nutzung des Angebots und bemerken Versandprobleme rechtzeitig.

## Hintergrundaufgaben automatisieren

Der Cron-Planer übernimmt die Planung und den Versand der Erinnerungen, die erneuten Versuche nach einem fehlgeschlagenen Versand der Haupt-E-Mail und die Überführung abgelaufener Gutscheine in den entsprechenden Status.

Im Werkzeug-Tab können Sie:

- den Cron-Job automatisch einrichten, sofern der Server das unterstützt;
- einen fertigen Befehl für den System- oder einen externen Planer kopieren;
- alle Aufgaben zur Kontrolle manuell ausführen;
- Zeitpunkt der letzten Ausführung und Zustand jeder Aufgabe einsehen;
- die Warteschlange für Wiederholungen und Erinnerungen prüfen.

## Die Kampagne vor Fehlern und unnötigen Rabatten schützen

- Für eine auslösende Bestellung und eine Regel wird der Gutschein nur ein einziges Mal erstellt.
- Bei Stornierung oder Rückerstattung der auslösenden Bestellung kann der zugehörige Gutschein automatisch deaktiviert werden.
- Zufällige Codes werden auf Eindeutigkeit geprüft.
- Das Ereignisprotokoll hilft, Fehler zu finden und Hintergrundvorgänge nachzuvollziehen.
- Die Aufbewahrungsdauer des Protokolls ist einstellbar.

## Was der Shop davon hat

- ein fertiges Szenario zur Kundenrückgewinnung direkt nach der abgeschlossenen Bestellung;
- persönliche Angebote statt eines einzigen Rabatts für alle;
- persönliche Gutscheine auf Basis der Standard-Warenkorbregeln von PrestaShop;
- konfigurierbare E-Mails und bis zu zwei Erinnerungen;
- Kontrolle über die Gültigkeitsdauer und automatische Stornierung hinfälliger Gutscheine;
- einen Funnel zur Gutscheinnutzung und ein Protokoll der Modulaktivität;
- Unterstützung für Multistore und E-Mails in den Sprachen des Shops.

---

## 4. Hauptfunktionen

### Regeln und Rabatte

- unbegrenzte Anzahl an Regeln;
- Regelpriorität und Stopp der weiteren Auswertung;
- prozentualer Rabatt, fester Betrag oder kostenloser Versand;
- Gültigkeitsdauer und Mindestbetrag der nächsten Bestellung je Regel;
- persönlicher, an den Kunden gebundener Gutschein;
- Schutz vor doppelter Vergabe je Bestellung und Regel;
- konfigurierbares Format des Gutscheincodes.

### Vergabebedingungen

- ausgewählte Bestellstatus;
- Betragsspanne der auslösenden Bestellung;
- Aktionszeitraum der Regel;
- laufende Bestellnummer des Kunden;
- Kundengruppen, Länder und Währungen;
- Produktkategorien und Marken;
- Einschluss- und Ausschlussmodi für Listenbedingungen.

### E-Mails und Erinnerungen

- eigene Haupt-E-Mail je Regel;
- bis zu zwei Erinnerungs-E-Mails;
- E-Mail-Texte für jede Sprache des Shops;
- dynamische Platzhalter;
- E-Mail-Vorschau;
- Testversand;
- automatischer Stopp der Erinnerungen nach Einlösung, Stornierung oder Ablauf des Gutscheins.

### Kontrolle und Automatisierung

- Funnel der Gutscheinstatus und Einlöse-Conversion;
- Liste der ausgestellten Gutscheine mit Filtern;
- manueller erneuter Versand von E-Mail und Erinnerungen;
- Warteschlange für Wiederholungen und Erinnerungen;
- Assistent zur Cron-Einrichtung;
- manuelle Ausführung der Aufgaben und Statuskontrolle;
- automatischer Ablauf der Gutscheingültigkeit;
- automatische Stornierung des Gutscheins bei ausgewählten Status der auslösenden Bestellung;
- Ereignisprotokoll mit Filtern und einstellbarer Aufbewahrungsdauer;
- Debug-Modus;
- Multistore-Unterstützung.

---

## 5. Fragen und Antworten

### Worin unterscheidet sich das Modul von einem gewöhnlichen Gutscheincode?

Ein gewöhnlicher Gutscheincode wird meist allen Kundinnen und Kunden vor oder während des laufenden Einkaufs beworben. Next Order Discount erstellt einen persönlichen Gutschein für einen bestimmten Kunden nach einer passenden Bestellung und motiviert ihn, für den nächsten Einkauf zurückzukommen.

### Wann wird der Gutschein erstellt?

Sobald die Bestellung einen der in der Regel ausgewählten Status erreicht und gleichzeitig alle übrigen Bedingungen dieser Regel erfüllt.

### Lässt sich der Gutschein nur nach der ersten Bestellung vergeben?

Ja. Setzen Sie die minimale und die maximale laufende Bestellnummer jeweils auf 1. Ebenso lässt sich eine Kampagne für die zweite, dritte oder eine spätere Bestellung einrichten.

### Kann man verschiedenen Kunden unterschiedliche Rabatte anbieten?

Ja. Erstellen Sie mehrere Regeln mit unterschiedlichen Bedingungen, Rabatten und Prioritäten. Berücksichtigen lassen sich Kundengruppe, Land, Währung, Bestellsumme, Produkte bestimmter Kategorien oder Marken und weitere Parameter.

### Können für eine Bestellung mehrere Gutscheine ausgestellt werden?

Ja, wenn die Bestellung auf mehrere Regeln zutrifft und in diesen Regeln der Stopp der weiteren Auswertung nicht aktiviert ist. Wenn nur ein Gutschein gewünscht ist, vergeben Sie Prioritäten und aktivieren Sie den Stopp bei der gewünschten Regel.

### Welche Rabattarten werden unterstützt?

Prozentualer Rabatt, fester Betrag und kostenloser Versand.

### Lässt sich für die nächste Bestellung ein Mindestbetrag festlegen?

Ja. Der Mindestbetrag für die Einlösung wird für jede Regel separat festgelegt.

### Versendet das Modul die E-Mail mit dem Gutschein selbst?

Ja. Nach dem Statuswechsel einer passenden Bestellung erstellt das Modul den Gutschein und versendet die E-Mail sofort über das Mailsystem von PrestaShop. Schlägt der Versand fehl, kommt die E-Mail für einen erneuten Versuch per Cron in die Warteschlange.

### Wie viele Erinnerungen lassen sich versenden?

Bis zu zwei. Jede Erinnerung kann deaktiviert werden, und das Versanddatum lässt sich ab der Haupt-E-Mail oder ab dem Ablaufdatum des Gutscheins berechnen.

### Wird nach der Einlösung des Gutscheins noch eine Erinnerung versendet?

Nein. Für eingelöste, abgelaufene oder stornierte Gutscheine werden keine Erinnerungen geplant.

### Was passiert bei Stornierung oder Rückerstattung der auslösenden Bestellung?

Ist der neue Bestellstatus in der Liste der Stornostatus enthalten, deaktiviert das Modul den zugehörigen Gutschein und markiert ihn als storniert.

### Lässt sich der E-Mail-Text ändern?

Ja. Für jede Regel und jede Sprache des Shops lassen sich Betreff und HTML-Inhalt der Haupt-E-Mail und der beiden Erinnerungen anpassen. Vorschau und Testversand stehen zur Verfügung.

### Sieht der Kunde einen neuen Block im Shop?

Nein. Das Modul arbeitet nach der Bestellung und informiert per E-Mail über den Gutschein; es muss daher kein Widget in das Theme eingebunden werden.

### Wie lässt sich der Erfolg der Kampagne bewerten?

Das Dashboard zeigt, wie viele Gutscheine erstellt, versendet, eingelöst, abgelaufen und storniert wurden, sowie die Conversion von erstellten zu eingelösten Gutscheinen.

### Wird Multistore unterstützt?

Ja. Daten und Einstellungen berücksichtigen den ausgewählten Shop; im Modus „Alle Shops“ steht eine zusammengefasste Statistik zur Verfügung.

### Welche Versionen werden unterstützt?

Das Modul ist für PrestaShop 8.1 und neuer ausgelegt. Die Mindestversion von PHP ist 7.2; der Server muss zudem den Systemanforderungen der installierten PrestaShop-Version entsprechen.

---

## 6. Suchbegriffe

Gutschein nächste Bestellung, Rabatt nächste Bestellung, Gutscheincode nach dem Kauf, automatischer Gutschein, persönlicher Gutschein, Wiederkauf, Folgekäufe, Kundenrückgewinnung, Kundenbindung, Rabatt nach Bestellung, E-Mail mit Gutschein, Gutschein-Erinnerung, Rabattautomatisierung, PrestaShop Rabattregeln, PrestaShop Gutschein, PrestaShop Treueprogramm, kostenloser Versand nächste Bestellung, Gutschein-Conversion

---

## 7. Text für den Bereich „Was ist neu“

**Version 1.0.0 — erste Veröffentlichung**

- Automatische Erstellung eines persönlichen Gutscheins, sobald die Bestellung den gewählten Status erreicht.
- Unbegrenzte Anzahl an Regeln mit Priorität und flexiblen Bedingungen.
- Prozentualer Rabatt, fester Betrag oder kostenloser Versand.
- Targeting nach Betrag und laufender Bestellnummer, Kampagnenzeitraum, Gruppen, Ländern, Währungen, Kategorien und Marken.
- Eigene E-Mails für jede Regel und jede Sprache des Shops.
- Bis zu zwei automatische Erinnerungen.
- Konfigurierbares Format des Gutscheincodes.
- Automatische Stornierung und Ablaufverarbeitung.
- Gutschein-Funnel, Warteschlange für Wiederholungen und Erinnerungen, Cron-Werkzeuge und Ereignisprotokoll.
- Multistore-Unterstützung.

---

## 8. Empfohlene Schwerpunkte für die Produktkarte

Der erste Bildschirm der Produktkarte sollte drei Fragen beantworten:

1. **Was macht das Modul?** Es erstellt nach der Bestellung einen persönlichen Gutschein.
2. **Wozu dient es?** Es gibt dem Kunden einen konkreten Grund zurückzukommen.
3. **Warum ist die Lösung praktisch?** Regeln, E-Mails, Erinnerungen und Kontrolle sind in einem Modul vereint.

Empfohlene Überschrift für den ersten Bildschirm:

> **Machen Sie aus der abgeschlossenen Bestellung einen Anlass für den nächsten Kauf**

Empfohlene Unterzeile:

> **Erstellen Sie nach dem Kauf persönliche Gutscheine, erinnern Sie Ihre Kunden an den Vorteil und verfolgen Sie das Ergebnis in einem einzigen Funnel.**

Für die Screenshots im Listing eignen sich am besten:

1. das Dashboard mit dem Gutschein-Funnel;
2. die Regelliste und die Prioritäten;
3. die Targeting-Bedingungen;
4. die Einstellung von Rabatt und Gültigkeitsdauer;
5. der Editor und die Vorschau der E-Mail;
6. die Liste der ausgestellten Gutscheine;
7. der Zustand von Cron und der Warteschlange für Wiederholungen und Erinnerungen.

Verwenden Sie auf jedem Bild eine einzige kurze Aussage statt einer Aufzählung aller Funktionen. Die Leitlinie der gesamten Produktkarte: **„Ein persönlicher Grund zurückzukommen — automatisch nach jeder passenden Bestellung“**.
