_Modulversion: 1.0.0 (Next Order Discount)_

**Next Order Discount** ist ein Modul, das dem Kunden automatisch einen persönlichen Gutschein für seine **nächste Bestellung** ausstellt, sobald seine aktuelle Bestellung den gewünschten Status erreicht.

Die Arbeitsweise des Moduls wird über **Regeln** gesteuert. In jeder Regel legen Sie fest, unter welchen Bedingungen der Kunde einen Gutschein erhält und welcher Rabatt gelten soll. Sie können so viele Regeln anlegen, wie Sie benötigen. Trifft eine Bestellung auf die Bedingungen einer Regel zu, erstellt das Modul einen Gutschein und übernimmt dessen gesamten Lebenszyklus: Es versendet die E-Mail an den Kunden und die Erinnerungen, überwacht die Gültigkeitsdauer und storniert den Gutschein, wenn die Bestellung zurückerstattet wird.

Was der Kunde erhält:

- einen persönlichen Gutscheincode für den nächsten Einkauf;
- eine E-Mail mit dem Gutschein direkt nach der Bestellung;
- ein bis zwei Erinnerungen, solange der Gutschein weder eingelöst noch abgelaufen ist;
- eine klare Gültigkeitsdauer und – falls gewünscht – einen Mindestbetrag für die nächste Bestellung.

**Funktionen des Moduls:**

- **Rabattregeln**: Legen Sie beliebig viele Regeln an, definieren Sie für jede eigene Bedingungen und einen eigenen Rabatt und steuern Sie die Reihenfolge ihrer Anwendung über die Priorität;
- drei Rabattarten: **Prozent**, **fester Betrag**, **kostenloser Versand**; Gültigkeitsdauer und Mindestbetrag der nächsten Bestellung je Regel;
- **flexible Auslösebedingungen**: Bestellstatus, bei denen ein Gutschein ausgegeben wird, Betragsspanne der auslösenden Bestellung, Aktivitätsfenster nach Datum, laufende Bestellnummer des Kunden (zum Beispiel „nur die erste Bestellung“), Ausschluss von Gastbestellungen sowie Listenbedingungen im Modus **All / Include / Exclude** — Kundengruppen, Länder, Währungen, Produktkategorien und Marken;
- die Option **„Stop after this rule“** und die Priorität: entweder ein Gutschein pro Bestellung oder mehrere Gutscheine aus verschiedenen Regeln;
- ein **eigenes Codeformat** für jede Regel: Länge, Zeichensatz (Buchstaben / Ziffern / alphanumerisch) und eine Vorlage mit dem Platzhalter `%key%` (zum Beispiel `NOD-%key%`);
- **eigene E-Mails je Regel**: die Gutschein-E-Mail und zwei Erinnerungs-E-Mails, getrennt **für jede Sprache des Shops**, mit Vorschau, Testversand und Sprachauswahl beim manuellen Versand aus der Gutscheinliste;
- **Erinnerungen**: 1. und 2. E-Mail, berechnet entweder ab dem Datum der Gutschein-E-Mail oder ab dem Ablaufdatum; sie enden von selbst, sobald der Gutschein eingelöst oder abgelaufen ist;
- die **automatische Stornierung** des Gutscheins, wenn die auslösende Bestellung in einen stornierten oder zurückerstatteten Status wechselt;
- Hintergrundaufgaben per **Cron** (E-Mail-Warteschlange, Planung der Erinnerungen, Ablauf der Gutscheine) mit Einrichtungsassistent, Zustandsprüfung und manueller Ausführung;
- ein **Dashboard** — Gutschein-Funnel (erzeugt → versendet → erinnert → eingelöst → abgelaufen → storniert), Conversion und ein Diagramm des Tagesverlaufs der letzten 30 Tage;
- ein **Protokoll (Logs)** der Modulereignisse mit Filtern und Aufbewahrungsdauer;
- Unterstützung für **Multistore** (Regeln, Gutscheine und Einstellungen im Kontext des ausgewählten Shops) und für mehrsprachige E-Mails.

**Beispiel:**

- Regel: „10 % Rabatt auf die nächste Bestellung, 30 Tage gültig, für Bestellungen ab 100 €“.
- Der Kunde hat für 150 € bestellt und die Bestellung hat den Status „Zahlung akzeptiert“ erreicht.
**→ Für den Kunden wurde ein persönlicher Gutschein über −10 % erstellt, die E-Mail wurde versendet, und nach einigen Tagen folgt eine Erinnerung, falls der Gutschein nicht eingelöst wurde.**

_Screenshot: Wie der Kunde seinen Gutschein für die nächste Bestellung per E-Mail erhält._
![img.png](img.png)



<a id="toc"></a>

## Inhalt

1. [Wo das Modul im Back Office zu finden ist](#t1)
2. [Welche Tabs es gibt](#t2)
3. [Wie das Modul arbeitet (Lebenszyklus des Gutscheins)](#t3)
4. [Tab Dashboard: Funnel und Tagesverlauf](#t4)
5. [Tab Regeln: die Regeltabelle](#t5)
    - [Aufbau der Tabelle](#t6)
    - [Verfügbare Aktionen](#t7)
    - [Priorität und Reihenfolge der Regeln](#t8)
6. [Regel anlegen und bearbeiten (Rule)](#t9)
    - [6.1 Tab Allgemein (Grundeinstellungen)](#t10)
    - [6.2 Tab Bedingungen](#t11)
    - [6.3 Tab Code (Codeformat)](#t12)
    - [6.4 Tab E-Mail (Nachrichten)](#t13)
    - [Aktionen des Formulars](#t14)
7. [Tab Gutscheine: ausgestellte Gutscheine](#t15)
    - [Filter](#t16)
    - [Spalten der Tabelle](#t17)
    - [Manueller Versand von E-Mail und Erinnerungen](#t18)
8. [Tab Einstellungen](#t19)
9. [Tab Cron/Werkzeuge: Hintergrundaufgaben](#t20)
    - [Cron einrichten](#t21)
    - [Zustand der Hintergrundaufgaben](#t22)
    - [Versandwarteschlange](#t23)
10. [Tab Protokolle: das Ereignisprotokoll](#t24)
11. [Support](#t25)
12. [Schnellstart (Einrichtung in 5–10 Minuten)](#t26)
13. [Diagnose-Checkliste](#t27)

---

<a id="t1"></a>

## 1. Wo das Modul im Back Office zu finden ist

1. Melden Sie sich im PrestaShop-Back-Office an.
2. Öffnen Sie den Bereich **Katalog**.
3. Suchen Sie den Eintrag **Next Order Discount**.

Das Modul legt einen eigenen Tab im Menü „Katalog“ an und öffnet sich auf seiner eigenen Seite mit internen Tabs (Dashboard, Regeln, Gutscheine, Einstellungen, Cron/Werkzeuge, Protokolle).

_Screenshot: Der Moduleintrag im Bereich „Katalog“._
![img_1.png](img_1.png)

<a id="t2"></a>

## 2. Welche Tabs es gibt

Beim Öffnen zeigt das Modul standardmäßig den Tab **Dashboard**. Verfügbar sind:

- **Dashboard** — Gutschein-Funnel, Conversion und der Tagesverlauf von Ausgabe, Versand und Einlösung der Gutscheine über die letzten 30 Tage. Wird standardmäßig geöffnet.
- **Regeln** (Rules) — Verwaltung der Regeln: anlegen, bearbeiten, aktivieren/deaktivieren, priorisieren, löschen. Genau hier werden die Rabatte und die Bedingungen ihrer Vergabe festgelegt.
- **Gutscheine** (Coupons) — Liste aller ausgestellten Gutscheine mit Regel, Kunde, Status und Gültigkeitsdauer; manueller erneuter Versand von E-Mail und Erinnerungen.
- **Einstellungen** (Settings) — allgemeine Moduleinstellungen: ein-/ausgeschaltet, Stornostatus für Gutscheine, Debug-Modus, Aufbewahrungsdauer des Protokolls.
- **Cron/Werkzeuge** (Cron/Tools) — Einrichtung der Hintergrundaufgaben (Cron): Installationsassistent, Links zu den Aufgaben, Zustandsprüfung, manuelle Ausführung, Momentaufnahme der Warteschlange.
- **Protokolle** (Logs) — Protokoll der Modulereignisse mit Filtern nach Stufe und Kanal.

Zusätzlich öffnet sich eine Unterseite:

- **Regel** (Rule) — das Formular zum Anlegen/Bearbeiten einer einzelnen Regel (wird aus dem Tab Regeln geöffnet).

Ist Multistore aktiviert, wird alles **im Kontext des oben ausgewählten Shops** gelesen und gespeichert. Im Kontext „Alle Shops“ werden Gutscheinlisten, Funnel, Verlaufsdiagramm und Warteschlange über alle Shops hinweg summiert angezeigt.

Am Fuß jeder Modulseite befindet sich der Block **Brauchen Sie Hilfe?** mit einem Link zum Kontaktformular von PrestaShop Addons.

_Screenshot: Die Leiste der internen Modul-Tabs._
![img_2.png](img_2.png)

---

<a id="t3"></a>

## 3. Wie das Modul arbeitet (Lebenszyklus des Gutscheins)

Wer die Gesamtlogik versteht, richtet die Regeln schneller ein.

1. **Auslöser.** Das Modul lauscht auf Bestellereignisse (Validierung der Bestellung und Statuswechsel). Sobald eine Bestellung in einen passenden Status gelangt, startet die Prüfung der Regeln.
2. **Auswahl der Regel.** Die Regeln werden **nach Priorität** geprüft (von oben nach unten in der Regeltabelle). Für jede Regel werden sämtliche Bedingungen kontrolliert. Passt die Regel, wird auf ihrer Grundlage ein Gutschein erstellt (eine persönliche PrestaShop-Warenkorbregel, an den Kunden gebunden).
   - Ist bei der ausgelösten Regel die Option **Nach dieser Regel stoppen** aktiviert, endet die Prüfung an dieser Stelle — der Kunde erhält **einen** Gutschein.
   - Ist die Option deaktiviert, werden auch die folgenden Regeln geprüft, und jede passende Regel kann **ihren eigenen** Gutschein ausstellen.
3. **Idempotenz.** Für eine auslösende Bestellung wird je Regel nur einmal ein Gutschein erstellt — wiederholte Hook-Auslösungen erzeugen keine Duplikate.
4. **E-Mail.** Direkt nach dem Erstellen des Gutscheins versucht das Modul, dem Kunden die E-Mail zu senden. Schlägt der Versand fehl, kommt die E-Mail in die Warteschlange und der Cron wiederholt den Versuch (siehe [Cron/Werkzeuge](#t20)). Der automatische Versand verwendet die Sprache der auslösenden Bestellung; ist sie nicht verfügbar, greift das Modul der Reihe nach auf die Sprache des Kundenkontos und auf die Standardsprache des Shops zurück.
5. **Erinnerungen.** Sind in der Regel Erinnerungen aktiviert, plant das Modul die 1. und die 2. E-Mail. Sie werden versendet, solange der Gutschein **weder eingelöst noch abgelaufen** ist.
6. **Einlösung.** Wendet der Kunde den Gutschein auf eine neue Bestellung an, wird der entsprechende Eintrag als **used** markiert und die Erinnerungen dazu enden.
7. **Ablauf.** Nach Ende der Gültigkeitsdauer wechselt der Gutschein in den Status **expired** (durch eine Hintergrundaufgabe).
8. **Stornierung.** Wechselt die **auslösende Bestellung** in einen Status aus der Stornoliste (standardmäßig „Storniert“ und „Rückerstattet“), wird der von ihr ausgestellte Gutschein deaktiviert und als **canceled** markiert; für diese Bestellung wird kein neuer Gutschein erstellt.

Die Gutscheinstatus, die Ihnen im Modul begegnen: **created** (erstellt) → **emailed** (versendet) → **reminded** (Erinnerung versendet) → **used** (eingelöst) / **expired** (abgelaufen) / **canceled** (storniert).

---

<a id="t4"></a>

## 4. Tab Dashboard: Funnel und Tagesverlauf

Der Tab **Dashboard** zeigt die Gesamtergebnisse der Regeln und die Entwicklung der Kennzahlen nach Tagen, im Kontext des ausgewählten Shops.

### Gutschein-Funnel (Coupon funnel)
Sechs Kacheln mit der Zahl der Gutscheine je Stufe und dem Anteil an den erzeugten Gutscheinen. Der Prozentwert steht neben der Zahl und auf dem farbigen Balken darunter; für **Erzeugt** wird kein Prozentwert ausgegeben, da dies der Basiswert ist:

- **Erzeugt** — insgesamt erzeugte Gutscheine (Basis für die Prozentwerte).
- **Versendet** — für wie viele die Gutschein-E-Mail versendet wurde.
- **Erinnert** — für wie viele mindestens eine Erinnerung versendet wurde.
- **Eingelöst** — von Kunden eingelöst.
- **Abgelaufen** — abgelaufen.
- **Storniert** — storniert (unter anderem wegen einer Rückerstattung der auslösenden Bestellung).

Unter dem Funnel steht die **Conversion (eingelöst vs. erzeugt)**: der Anteil der eingelösten an den erzeugten Gutscheinen. Das ist die zentrale Erfolgskennzahl der Aktion.

> Die Stufen des Funnels können sich überschneiden und müssen zusammen nicht 100 % ergeben: Ein eingelöster oder abgelaufener Gutschein zählt beispielsweise weiterhin zu den zuvor versendeten, wenn die Gutschein-E-Mail erfolgreich hinausgegangen ist.

### Tagesverlauf (Daily dynamics)

Das Liniendiagramm zeigt die Kennzahlen je Tag über die letzten **30 Tage**, den heutigen eingeschlossen:

- **Erzeugt** — wie viele Gutscheine an diesem Tag erstellt wurden;
- **Versendet** — wie viele Gutschein-E-Mails an diesem Tag erfolgreich versendet wurden;
- **Eingelöst** — wie viele Gutscheine an diesem Tag eingelöst wurden.

Alle drei Linien nutzen dieselbe Skala und lassen sich daher direkt vergleichen. Fahren Sie mit dem Mauszeiger über einen Punkt, um die Werte des Tages zu sehen. Ein Klick auf eine Kennzahl in der Legende blendet die zugehörige Linie aus oder wieder ein; der Maßstab des Diagramms wird danach automatisch neu berechnet. Hat es in den letzten 30 Tagen kein einziges Ereignis gegeben, wird das Diagramm nicht angezeigt.

> Im Multistore werden die Daten im Kontext des oben ausgewählten Shops angezeigt (in „Alle Shops“ summiert).

_Screenshot: Der Tab Dashboard (Funnel und Tagesverlauf)._
![img_3.png](img_3.png)

---

<a id="t5"></a>

## 5. Tab Regeln: die Regeltabelle

Dies ist der zentrale Arbeitsbereich: Hier sind alle Regeln zur Gutscheinvergabe für den aktuellen Shop aufgeführt. Die Schaltfläche **Regel hinzufügen** (im Seitenkopf) öffnet das Formular zum Anlegen einer Regel. Nach einer Neuinstallation legt das Modul nicht automatisch eine aktive Regel an: Solange Sie nicht selbst eine Regel hinzufügen und aktivieren, werden keine Gutscheine ausgegeben.

Gibt es noch keine Regeln, erscheint anstelle der Tabelle der Hinweis **Noch keine Rabattregeln** sowie eine zusätzliche Schaltfläche **Regel hinzufügen** — klicken Sie darauf oder auf die gleichnamige Schaltfläche im Seitenkopf, um Ihre erste Regel anzulegen.

<a id="t6"></a>

### Aufbau der Tabelle

Jede Zeile ist eine Regel. Die Spalten:

- **Priorität** — die Priorität (eine Zahl) und Pfeile zum Verschieben nach oben/unten. In dieser Reihenfolge werden die Regeln geprüft.
- **Name** — der interne Name der Regel. Daneben können Badges stehen:
  - **Stopp** — die Option „nach dieser Regel stoppen“ ist aktiviert;
  - ein Badge mit Glocke und Tagen (zum Beispiel `1T · 3T`) — Erinnerungen sind aktiviert, samt ihrem Zeitplan.
- **Rabatt** — das Ergebnis des Rabatts (zum Beispiel `10%`, `15 €`, `Kostenloser Versand`).
- **Gültigkeit** — die Gültigkeitsdauer des Gutscheins in Tagen.
- **Auslösende Status** — die Bestellstatus, bei denen die Regel auslöst (oder das Badge **Beliebiger Status**, wenn es keine Einschränkung gibt).
- **Bedingungen** — die Badges der aktiven Bedingungen (Gruppen, Länder, Währungen, Kategorien, Marken, Bereiche). Ein Strich bedeutet: keine Bedingungen.
- **Aktiv** — der Schalter zum Aktivieren der Regel (Ja/Nein).
- **Aktionen** — Bearbeiten und Löschen.

_Screenshot: Die Regeltabelle mit Spalten und Badges._
![img_4.png](img_4.png)

<a id="t7"></a>

### Verfügbare Aktionen

**Bearbeiten** — öffnet das Formular zum Bearbeiten der Regel.

**Löschen** — löscht die Regel (mit Bestätigung). Bereits auf ihrer Grundlage ausgestellte Gutscheine bleiben in der Tabelle Gutscheine erhalten.

**Aktiv (Ja/Nein)** — ein schneller Schalter direkt in der Zeile: Eine deaktivierte Regel nimmt an der Gutscheinvergabe nicht teil.

**Prioritätspfeile (▲ ▼)** — verschieben die Regel in der Prüfreihenfolge nach oben oder unten.

_Screenshot: Die Schaltflächen Bearbeiten / Löschen und der Schalter Aktiv._
![img_5.png](img_5.png)

<a id="t8"></a>

### Priorität und Reihenfolge der Regeln

Die Regeln werden **von oben nach unten** nach Priorität geprüft. Die Reihenfolge ist wichtig, wenn:

- bei mehreren Regeln **Nach dieser Regel stoppen** aktiviert ist — es greift die erste passende Regel gemäß Priorität, die übrigen werden nicht geprüft;
- Sie möchten, dass eine „spezifischere“ Regel (zum Beispiel für eine VIP-Gruppe) die Chance hat, vor einer allgemeinen Regel zu greifen.

Die Prioritäten werden automatisch als lückenlose Folge von 1 bis N geführt (ein Verschieben mit den Pfeilen nummeriert alles neu).

_Screenshot: Verschieben einer Regel mit den Prioritätspfeilen._
![img_6.png](img_6.png)

---

<a id="t9"></a>

## 6. Regel anlegen und bearbeiten (Rule)

Das Regelformular öffnet sich über die Schaltfläche **Regel hinzufügen** oder **Bearbeiten**. Es gliedert sich in vier Tabs: **Allgemein**, **Bedingungen**, **Code**, **E-Mail**. Unten befinden sich die Schaltflächen **Speichern** und **Abbrechen** (für alle Tabs gemeinsam).

<a id="t10"></a>

### 6.1 Tab Allgemein (Grundeinstellungen)

**Regelname** (Rule name) — der interne Name, der in der Regeltabelle angezeigt wird. Pflichtfeld.

**Gutscheinname** (Voucher name) — der Name des Gutscheins, den der Kunde sieht. Leer = es wird der Standardwert „Next Order Discount“ verwendet.

**Gutscheinbeschreibung** (Voucher description) — eine optionale Beschreibung, die auf dem Gutschein gespeichert wird (im Back Office sichtbar).

**Aktiv** (Active) — ist die Regel aktiviert (Ja/Nein).

Block Rabatt:

- **Rabattart** (Discount type):
  - **Prozent (%)** — ein Prozentsatz der Summe der nächsten Bestellung;
  - **Fester Betrag** — ein fester Betrag (in der Währung);
  - **Kostenloser Versand**.
- **Rabattwert** (Discount value) — die Höhe des Rabatts. Beim Prozentsatz auf 100 begrenzt. Die Beschriftung rechts (`%` oder das Währungszeichen) richtet sich automatisch nach der gewählten Art; bei der Art **Kostenloser Versand** wird das Feld ausgeblendet (ein Rabattwert ist dann nicht nötig).
- **Gültigkeitsdauer (Tage)** (Validity period) — die Gültigkeitsdauer des Gutscheins in Tagen (ganze Zahl, mindestens 1).
- **Mindestbetrag der nächsten Bestellung** (Minimum next order amount) — der Mindestbetrag der nächsten Bestellung, ab dem der Gutschein einlösbar ist. `0` = ohne Einschränkung.

Block Vergabelogik:

- **Nach dieser Regel stoppen** (Stop after this rule) — bei Ja werden nach dem Auslösen dieser Regel keine weiteren mehr geprüft (der Kunde erhält einen Gutschein). Bei Nein können auch andere passende Regeln ihre Gutscheine ausstellen.

Block Erinnerungen:

- **Erinnerungen senden** (Send reminders) — Erinnerungs-E-Mails zu einem ungenutzten Gutschein aktivieren. Die Erinnerungen enden automatisch, sobald der Gutschein eingelöst oder abgelaufen ist.
- **Zeitpunkt der Erinnerungen** (Reminder timing) — wovon die Tage gezählt werden:
  - **Tage nach der Gutschein-E-Mail** — N Tage nach der Gutschein-E-Mail;
  - **Tage vor Ablauf des Gutscheins** — N Tage vor Ablauf des Gutscheins.
- **Erste Erinnerung (Tage)** / **Zweite Erinnerung (Tage)** — der Zeitpunkt der 1. und der 2. Erinnerung. `0` oder leer = diese Erinnerung wird nicht gesendet.

_Screenshot: Der Tab Allgemein des Regelformulars._
![img_7.png](img_7.png)

<a id="t11"></a>

### 6.2 Tab Bedingungen

Alle festgelegten Bedingungen müssen gleichzeitig erfüllt sein (UND-Logik). Eine leere Bedingung oder eine mit `Alle` schränkt nichts ein.

**Auslösen bei Bestellstatus** (Trigger on order statuses) — die Status, in denen eine Bestellung einen Gutschein auslösen kann. Die Regel wird beim Anlegen der Bestellung und bei jedem Wechsel ihres Status geprüft. Eine leere Liste bedeutet, dass der Status die Regel nicht einschränkt. Für die Auswahl mehrerer Status halten Sie Strg/Cmd gedrückt. Der Gutschein wird nur ausgegeben, wenn die Bestellung auch die übrigen Bedingungen der Regel erfüllt.

Listenbedingungen — jede besitzt einen Modus und eine Liste:

- **Kundengruppen** (Customer groups);
- **Länder** (Countries) — nach der Lieferadresse der Bestellung;
- **Währungen** (Currencies) — die Währung der Bestellung;
- **Produktkategorien** (Product categories) — die Kategorien der bestellten Artikel;
- **Marken** (Brands) — die Marken (Hersteller) der bestellten Artikel.

Der Modus jeder Bedingung:

- **Alle (keine Einschränkung)** — nichts einschränken (die Liste wird ignoriert);
- **Nur die ausgewählten** — die Regel gilt nur für die ausgewählten Elemente;
- **Alle außer den ausgewählten** — die Regel gilt für alle außer den ausgewählten Elementen.

Die Auswahlliste erscheint nur in den Modi **Nur die ausgewählten** / **Alle außer den ausgewählten**; im Modus **Alle** ist sie ausgeblendet, damit sie nicht stört.

Bereichsbedingungen:

- **Summe der auslösenden Bestellung** (Source order total) — die Betragsspanne der auslösenden Bestellung (**Min.** / **Max.**). `0` = keine Einschränkung an dieser Grenze.
- **Aktiver Zeitraum** (Active date window) — das Aktivitätsfenster der Regel (**Von** / **Bis**). Beide leer = die Regel ist immer aktiv.
- **Anzahl der Bestellungen des Kunden** (Customer order number) — wie viele Bestellungen der Kunde haben muss (**Min.** / **Max.**). Beide Werte auf `1` = nur die erste Bestellung. `0` = ohne Einschränkung. Die Bestellungen eines Kunden werden im aktuellen Shop anhand der E-Mail gezählt, auch wenn PrestaShop für dieselbe Adresse mehrere Kundendatensätze angelegt hat; die aktuelle Bestellung ist in dieser Zahl bereits enthalten.
- **Nur registrierte Kunden** (Registered customers only) — bei **Ja** nehmen Gastbestellungen nicht an der Regel teil. Bei **Nein** prüft die Regel registrierte Kunden und Gäste gleichermaßen. Für Szenarien zur „ersten Bestellung“ und zur Kundenrückgewinnung empfiehlt es sich, diese Option zu aktivieren: Bei einer Gastbestellung kann PrestaShop jedes Mal einen neuen Kundendatensatz anlegen, sodass sich ein wiederkehrender Gast nur über eine Übereinstimmung der E-Mail-Adresse zuverlässig erkennen lässt.

> Die Bedingungen zu Kategorien und Marken beziehen sich auf die Artikel der **auslösenden Bestellung**. Das Modul lädt die Produktdaten nur dann, wenn mindestens eine aktive Regel tatsächlich nach Kategorien oder Marken filtert — das spart bei allen anderen Bestellungen Ressourcen.

_Screenshot: Der Tab Bedingungen._
![img_8.png](img_8.png)

<a id="t12"></a>

### 6.3 Tab Code (Codeformat)

Das Format des Gutscheincodes wird **je Regel** festgelegt. Jedes Feld kann leer bleiben — dann greift der eingebaute Standardwert.

- **Schlüssellänge** (Key length) — die Anzahl der Zufallszeichen in `%key%` (auf den Bereich 4–32 begrenzt).
- **Schlüsseltyp** (Key type) — der Zeichensatz für die Erzeugung:
  - **Buchstaben (A-Z)** — nur Buchstaben;
  - **Ziffern (0-9)** — nur Ziffern;
  - **Alphanumerisch (A-Z, 0-9)** — Buchstaben und Ziffern.
- **Schlüsselvorlage** (Key template) — die Codevorlage mit dem Platzhalter `%key%`. Beispiel: `NOD-%key%` → `NOD-AB12CD8X`.

_Screenshot: Der Tab Code._
![img_9.png](img_9.png)

<a id="t13"></a>

### 6.4 Tab E-Mail (Nachrichten)

Jede Regel hat **eigene E-Mails**, vorbelegt mit der Standardvorlage. Drei Typen sind konfigurierbar:

- **Gutschein-E-Mail** (Coupon email) — die Gutschein-E-Mail (wird bei der Ausgabe des Gutscheins versendet);
- **E-Mail der ersten Erinnerung** (First reminder email) — die erste Erinnerung;
- **E-Mail der zweiten Erinnerung** (Second reminder email) — die zweite Erinnerung.

Für jeden Typ:

- ein **Sprachumschalter** (nach ISO-Code) — Betreff und HTML werden **für jede Sprache** des Shops getrennt festgelegt;
- das Feld **Betreff** (Subject) — der Betreff der E-Mail;
- das Feld **HTML-Inhalt** (HTML content) — der HTML-Textkörper der E-Mail;
- unter dem HTML-Feld die Zeile **Verfügbare Platzhalter (zum Einfügen anklicken)**: anklickbare „Chips“ mit Platzhaltern. Ein Klick fügt den Platzhalter direkt an der Cursorposition in das HTML-Feld ein — praktisch, um ihn nicht von Hand tippen zu müssen.

Platzhalter, die beim Versand durch echte Werte ersetzt werden:

- Gutschein: `{coupon_code}`, `{coupon_value}`, `{valid_to}`, `{minimum_amount}`;
- Kunde: `{customer_firstname}`, `{customer_lastname}`, `{customer_fullname}`, `{customer_title}` (die Anrede, zum Beispiel „Mr“/„Mrs“; leer, wenn das Geschlecht nicht hinterlegt ist), `{customer_email}`;
- Shop: `{shop_name}`, `{shop_url}`, `{shop_logo}`. Der Platzhalter `{shop_url}` wird beim Versand unterstützt, muss aber bei Bedarf von Hand in das HTML eingetragen werden.

Jede Sprache verwendet ihren eigenen Betreff und ihr eigenes HTML — Texte aus anderen Sprachversionen werden nicht übernommen. Sind für die gewählte Sprache weder Betreff noch HTML gespeichert, greift das Modul auf die eingebaute Vorlage zurück: Französisch für **FR**, Englisch für **EN** und alle übrigen Sprachen. Füllen und speichern Sie die E-Mails für alle Sprachen des Shops, bevor Sie eine Regel aktivieren.

Aktionen unter jeder E-Mail:

- **Vorschau** (Preview) — Vorschau der E-Mail mit Beispielwerten für die Platzhalter (öffnet sich in einem Fenster).
- **Test-E-Mail senden** (Send test email) — Versand einer Testkopie an die angegebene Adresse (ebenfalls mit Beispielwerten). Praktisch, um das Layout vor dem Echtversand zu prüfen.

_Screenshot: Der Tab E-Mail._
![img_10.png](img_10.png)

<a id="t14"></a>

### Aktionen des Formulars

- **Speichern** — prüft und speichert die Regel und kehrt zur Regelliste zurück. Bei Validierungsfehlern bleibt das Formular mit Hinweisen geöffnet.
- **Abbrechen** — kehrt ohne Speichern zur Liste zurück.

_Screenshot: Die Schaltflächen Speichern / Abbrechen._
![img_13.png](img_13.png)

---

<a id="t15"></a>

## 7. Tab Gutscheine: ausgestellte Gutscheine

Der Tab **Gutscheine** ist die Liste aller erzeugten Gutscheine (nur Ansicht + manuelle Versandaktionen).

<a id="t16"></a>

### Filter

- **Status** — Filter nach Gutscheinstatus (created / emailed / reminded / used / expired / canceled) oder „Alle Status“.
- **Code** — Suche nach dem Gutscheincode.
- Die Schaltflächen **Filtern** und **Zurücksetzen**.

Die Liste ist seitenweise aufgeteilt (30 Einträge pro Seite).

_Screenshot: Die Filter des Tabs Gutscheine._
![img_11.png](img_11.png)

<a id="t17"></a>

### Spalten der Tabelle

- **Code** — der Gutscheincode.
- **Kunde** — Name und E-Mail des Kunden (oder seine ID, falls der Name nicht verfügbar ist).
- **Auslösende Bestellung** — die Nummer der Bestellung, die den Gutschein ausgelöst hat.
- **Regel** — die Regel, auf deren Grundlage der Gutschein erstellt wurde.
- **Status** — der aktuelle Status (mit farbigem Badge). Daneben können die Badges `1` / `2` stehen — sie zeigen, welche Erinnerungen bereits versendet wurden.
- **Gültig bis** — die Gültigkeitsdauer des Gutscheins; Datum und Uhrzeit erscheinen im Format der aktuellen PrestaShop-Locale.
- **Erstellt** — Datum und Uhrzeit der Erstellung im Format der aktuellen PrestaShop-Locale.
- **Aktionen** — die manuellen Aktionen (siehe unten).

_Screenshot: Die Gutscheintabelle._
![img_12.png](img_12.png)

<a id="t18"></a>

### Manueller Versand von E-Mail und Erinnerungen

Solange der Gutschein **noch einlösbar** ist (weder used noch expired oder canceled), stehen in der Spalte Aktionen zur Verfügung:

- **Sprache der zu sendenden E-Mail** — die Sprachauswahl für den manuellen Versand (wird angezeigt, wenn im Shop mehr als eine Sprache installiert ist). Standardmäßig ist die Sprache der auslösenden Bestellung ausgewählt; der gewählte Wert gilt sowohl für den erneuten Versand des Gutscheins als auch für den manuellen Versand einer Erinnerung;
- die Schaltfläche mit dem **Umschlag** (Tooltip **Die Gutschein-E-Mail erneut an den Kunden senden**) — erneut senden;
- die Schaltflächen mit **Glocke und der Nummer 1 / 2** — die entsprechende Erinnerung sofort senden (sie erscheinen, wenn diese Erinnerungen in der Regel des Gutscheins aktiviert sind).

Für eingelöste, abgelaufene und stornierte Gutscheine sind die Aktionen nicht verfügbar — deren E-Mail und Erinnerungen sind gegenstandslos.

_Screenshot: Die Schaltflächen für erneuten Versand und Erinnerungen in der Gutscheinzeile._
![img_13.png](img_13.png)

---

<a id="t19"></a>

## 8. Tab Einstellungen

Der Tab **Einstellungen** enthält die allgemeinen Moduleinstellungen (Rabatte und Bedingungen werden in den Regeln festgelegt, nicht hier).

- **Modul aktiv** (Module active) — der Hauptschalter. Bei **Nein** werden für neue Bestellungen keine Gutscheine ausgegeben, unabhängig von den Regeln.
- **Gutschein bei diesen Bestellstatus stornieren** (Cancel coupon on order statuses) — die Bestellstatus, bei deren Erreichen der von dieser Bestellung ausgegebene Gutschein **storniert** wird (er wird deaktiviert und als canceled markiert), und für diese Bestellung wird kein neuer Gutschein erstellt. Standard: „Storniert“ und „Rückerstattet“. Leer = nie automatisch stornieren. Mehrfachauswahl mit Strg/Cmd.
- **Debug-Modus** (Debug mode) — ausführliche Protokollierung zur Fehlersuche. Im Produktivbetrieb deaktiviert lassen.
- **Protokolle aufbewahren für (Tage)** (Keep logs for) — die Aufbewahrungsdauer der Protokolleinträge; ältere werden automatisch gelöscht (während des Cron-Laufs). `0` = unbegrenzt aufbewahren.

Klicken Sie auf **Speichern**, um die Änderungen zu übernehmen. Die Einstellungen werden im Kontext des aktuellen Shops gespeichert (Multistore).

_Screenshot: Der Tab Einstellungen._
![img_14.png](img_14.png)

---

<a id="t20"></a>

## 9. Tab Cron/Werkzeuge: Hintergrundaufgaben

Nach dem Erstellen eines Gutscheins versucht das Modul sofort, die Haupt-E-Mail zu versenden. Schlägt der Versand fehl, gelangt die E-Mail in die Warteschlange und der **Cron** wiederholt den Versuch. Der Cron plant und versendet außerdem die Erinnerungs-E-Mails und überführt Gutscheine mit abgelaufener Frist in den Status **expired**.

<a id="t21"></a>

### Cron einrichten

Der empfohlene Weg ist, **eine Zeile** in die Crontab des Servers einzutragen, die alle 5 Minuten die kombinierte Aufgabe über HTTP aufruft. Dieser Weg funktioniert auf jedem Hosting und ist unabhängig von der PHP-Version des Servers.

Im Tab finden Sie:

- **Installation mit einem Klick** (One-click install) — ist die automatische Einrichtung der Crontab auf dem Server möglich, fügt die Schaltfläche **Cron automatisch einrichten** die nötige Zeile hinzu, und **Cron entfernen** nimmt sie wieder heraus. Die Zeile wird mit Markierungen versehen und auch bei der Deinstallation des Moduls entfernt. Ist die automatische Einrichtung nicht verfügbar (zum Beispiel weil `shell_exec` beim Shared Hosting gesperrt ist), erklärt das Modul die Ursache und schlägt vor, die Zeile von Hand zu kopieren.
- **Crontab-Zeile (curl / wget)** — fertige Zeilen zum manuellen Eintragen in die Crontab.
- **Oder einen externen Cron-Dienst verwenden** — die URL für externe Web-Cron-Dienste (zum Beispiel cron-job.org), mit einem Intervall von 5 Minuten.
- **Alle Aufgaben jetzt ausführen** — manueller Start aller Aufgaben auf einmal (eine schnelle Probe, ob die URL funktioniert).
- **Ihr Server** — eine Prüfung der Umgebung: PHP-Version, Vorhandensein von curl (CLI), Verfügbarkeit von shell_exec — um Ihnen den funktionierenden Weg zu zeigen.

> **Halten Sie den Token geheim.** Die URLs der Aufgaben enthalten einen geheimen Token: Wer eine URL kennt, kann die zugehörige Aufgabe ausführen.

_Screenshot: Der Block zur Cron-Einrichtung._
![img_15.png](img_15.png)

<a id="t22"></a>

### Zustand der Hintergrundaufgaben

Die Tabelle **Aufgaben** listet die Hintergrundaufgaben auf, dazu ihren empfohlenen Zeitplan, den Zeitpunkt der letzten Ausführung, ihre persönliche URL, den Zustand der Sperre und eine Schaltfläche zur manuellen Ausführung.

Die Aufgaben des Moduls:

- **Versandwarteschlange verarbeiten** — wiederholt den fehlgeschlagenen Versand der Haupt-E-Mail und versendet die geplanten Erinnerungen. Empfohlen: **alle 5 Minuten**.
- **Gutschein-Erinnerungen planen** — findet fällige Erinnerungen und stellt sie in die Warteschlange. Empfohlen: **alle 30 Minuten**.
- **Abgelaufene Gutscheine verfallen lassen** — überführt abgelaufene Gutscheine in den Status expired. Empfohlen: **einmal täglich**.

Die Spalte **Letzte Ausführung** zeigt den Zustand der Aufgabe: **OK** (sie läuft pünktlich), **Überfällig** (sie ist im Verzug), **Läuft nicht** (sie wurde lange nicht ausgeführt), **Nie ausgeführt** (noch kein einziges Mal). Die Spalte **Sperre** zeigt, ob die Aufgabe gerade ausgeführt wird (**Läuft**) oder frei ist (**Frei**) — die Sperre verhindert, dass sich zwei Läufe überschneiden.

Ein eigener Block **Verwalteter Cron** gibt an, ob die Cron-Zeile vom Modul selbst eingerichtet wurde.

_Screenshot: Die Aufgabentabelle mit Zeitplan und aktuellem Zustand._
![img_16.png](img_16.png)

<a id="t23"></a>

### Versandwarteschlange

Unten befindet sich eine Momentaufnahme der Warteschlange: **Ausstehend / In Bearbeitung / Erledigt / Fehlgeschlagen**. Ein wachsender Wert bei „Ausstehend“ oder auffällig viele fehlgeschlagene Einträge sind ein Anlass, Cron und Mail-Einstellungen zu prüfen.

_Screenshot: Momentaufnahme der Versandwarteschlange._
![img_17.png](img_17.png)

---

<a id="t24"></a>

## 10. Tab Protokolle: das Ereignisprotokoll

Der Tab **Protokolle** ist das Ereignisprotokoll des Moduls (Gutscheinvergabe, E-Mail-Versand, Hook-Fehler usw.).

- Der Filter **Stufe** — die Stufe des Eintrags: debug / info / warning / error (oder „Alle“).
- Der Filter **Kanal** — der Kanal (zum Beispiel `cron`, `queue`, `coupon`).
- Die Spalten: **Datum**, **Stufe** (mit farbigem Badge), **Kanal**, **Meldung** (mit Kontextdetails), **Korrelation** (die ID, die Einträge desselben Ereignisses miteinander verknüpft).

Die Liste ist seitenweise aufgeteilt. Der Detailgrad der Protokollierung hängt vom **Debug-Modus** ab, die Aufbewahrungsdauer von **Protokolle aufbewahren für** (beide Einstellungen finden Sie im Tab [Einstellungen](#t19)). Im Kontext eines bestimmten Shops werden auch allgemeine Cron- und Warteschlangeneinträge angezeigt, die ohne Bindung an einen einzelnen Shop entstanden sind: So sehen Sie Hintergrundfehler selbst bei deaktiviertem Debug-Modus. Der Eintrag zur Ausgabe eines Gutscheins enthält die `id_lang` und den ISO-Code der Sprache, in der die automatische E-Mail versendet wird.

_Screenshot: Der Tab Protokolle mit den Filtern._
![img_18.png](img_18.png)

---

<a id="t25"></a>

## 11. Support

Am Fuß der Modulseiten befindet sich der Block **Brauchen Sie Hilfe?** mit einem Link zum offiziellen Kontaktformular von PrestaShop Addons:

https://addons.prestashop.com/contact-form.php

Wenden Sie sich an uns, wenn:

- Sie Hilfe bei der Ersteinrichtung der Regeln oder des Cron benötigen;
- sich Gutscheine oder E-Mails nicht wie erwartet verhalten;
- Sie eine Erweiterung oder Anpassung der Funktionen benötigen;
- Fehler oder instabiles Verhalten auftreten;
- Sie Ideen zur Verbesserung haben.

_Screenshot: Der Support-Block._
![img_19.png](img_19.png)

---

<a id="t26"></a>

## 12. Schnellstart (Einrichtung in 5–10 Minuten)

1. Öffnen Sie den Tab **Einstellungen** und setzen Sie **Modul aktiv = Ja**. Konfigurieren Sie bei Bedarf **Gutschein bei diesen Bestellstatus stornieren**. Klicken Sie auf **Speichern**.
2. Richten Sie den **Cron** im Tab **Cron/Werkzeuge** ein: Klicken Sie auf **Cron automatisch einrichten** (sofern verfügbar) oder kopieren Sie die empfohlene Zeile in die Crontab bzw. in einen externen Web-Cron-Dienst. Klicken Sie zur Kontrolle auf **Alle Aufgaben jetzt ausführen**.
3. Legen Sie im Tab **Regeln → Regel hinzufügen** eine Regel an:
   - **Allgemein**: Name, Art und Höhe des Rabatts, Gültigkeitsdauer und bei Bedarf der Mindestbetrag der nächsten Bestellung sowie die Erinnerungen;
   - **Bedingungen**: die Bestellstatus, bei denen ein Gutschein ausgegeben wird, und bei Bedarf Einschränkungen (Gruppen, Länder, Beträge, Bestellnummer usw.); entscheiden Sie bei Regeln zur ersten bzw. zur wiederholten Bestellung, ob **Nur registrierte Kunden** aktiviert werden soll;
   - **Code**: das Codeformat (oder belassen Sie den Standard);
   - **E-Mail**: prüfen Sie die Texte der E-Mails und nutzen Sie **Vorschau** und **Test-E-Mail senden**.
4. Speichern Sie die Regel und vergewissern Sie sich, dass sie auf **Aktiv = Ja** steht.
5. Prüfen Sie die Regel an einer Testbestellung: Setzen Sie die Bestellung auf einen der in der Regel genannten Status und vergewissern Sie sich, dass im Tab **Gutscheine** ein Gutschein erscheint und die E-Mail versendet wurde. Ist der erste Versuch fehlgeschlagen, starten Sie die Hintergrundaufgaben im Tab **Cron/Werkzeuge** manuell.
6. Verfolgen Sie die Ergebnisse im Tab **Dashboard**: Bewerten Sie Funnel, Conversion und den Tagesverlauf der letzten 30 Tage.

---

<a id="t27"></a>

## 13. Diagnose-Checkliste

**Es wird kein Gutschein erstellt:**

1. **Modul aktiv = Ja** (Einstellungen).
2. Es gibt mindestens eine Regel mit **Aktiv = Ja** (Regeln).
3. Die Bestellung wechselt tatsächlich in einen der **Auslösenden Status** der Regel (oder die Regel akzeptiert „beliebigen Status“).
4. Die Bestellung erfüllt **alle** Bedingungen der Regel: Betragsspanne, Datumsfenster, laufende Bestellnummer des Kunden, Kundentyp (Gast oder registriert), Gruppen/Länder/Währungen/Kategorien/Marken.
5. Prüfen Sie die Reihenfolge: Hat eine höher priorisierte Regel die Option **Nach dieser Regel stoppen**, werden die darunterliegenden Regeln nicht geprüft.
6. Werfen Sie einen Blick in die **Protokolle** (bei **Debug-Modus = Ja** gibt es mehr Einträge).

**Der Kunde erhält keine E-Mail:**

1. Prüfen Sie die Mail-Konfiguration des Shops; senden Sie eine **Test-E-Mail** aus dem Tab E-Mail der Regel.
2. Für einen bestimmten Gutschein können Sie die gewünschte Sprache wählen und im Tab Gutscheine auf die Schaltfläche mit dem Umschlag klicken.
3. Ist der erste Versandversuch fehlgeschlagen, vergewissern Sie sich, dass der **Cron** läuft (Cron/Werkzeuge → Aufgabe **Versandwarteschlange verarbeiten**, Status **OK**), und klicken Sie für einen erneuten Versuch auf **Alle Aufgaben jetzt ausführen**.
4. In der Warteschlange sollten keine hängenden Einträge mit **Ausstehend** und keine wachsende Zahl **Fehlgeschlagen** stehen (Cron/Werkzeuge → **Versandwarteschlange**).
5. Kam die E-Mail in der falschen Sprache an, prüfen Sie den Text dieser Sprache im Tab **E-Mail** der Regel. Automatisch verwendet das Modul die Sprache der Bestellung; beim manuellen Versand die Sprache, die neben den Aktionsschaltflächen ausgewählt ist.

**Es werden keine Erinnerungen versendet:**

1. Die Regel hat **Erinnerungen senden = Ja** und einen festgelegten Zeitpunkt (**Erste/Zweite Erinnerung** > 0).
2. Die Aufgabe **Gutschein-Erinnerungen planen** läuft (Cron/Werkzeuge).
3. Der Gutschein ist **noch einlösbar** (weder used noch expired oder canceled) — für nicht mehr gültige Gutscheine werden keine Erinnerungen versendet.

**Ein Gutschein wurde unerwartet storniert:**

- Die auslösende Bestellung ist in einen Status aus der Liste **Gutschein bei diesen Bestellstatus stornieren** gewechselt (Einstellungen). Nehmen Sie den Status aus der Liste, wenn dieses Verhalten nicht gewünscht ist.

**Gutscheine laufen nicht ab (sie bleiben in den alten Status):**

- Die Aufgabe **Abgelaufene Gutscheine verfallen lassen** läuft nicht — prüfen Sie den Cron (Cron/Werkzeuge).
