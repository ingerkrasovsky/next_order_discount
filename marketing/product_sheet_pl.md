# Karta produktu modułu Next Order Discount dla PrestaShop

Gotowe polskie teksty do karty produktu, strony modułu i materiałów sprzedażowych. Sformułowania opisują rzeczywiste możliwości wersji 1.0.0, bez obiecywania gwarantowanego wzrostu sprzedaży.

---

## 1. Nazwa modułu

### Wariant zalecany

**Next Order Discount: osobisty kupon na następne zamówienie**

Nazwa od razu wyjaśnia mechanizm działania modułu, podkreśla osobisty charakter oferty i zawiera główną frazę wyszukiwania „kupon na następne zamówienie”.

### Wariant z akcentem na cel biznesowy

**Osobisty kupon na następne zamówienie — odzyskuj klientów**

### Wariant z akcentem na automatyzację

**Automatyczny osobisty kupon po zakupie**

---

## 2. Krótki opis

**Twórz powód do ponownego zakupu już po zakończonym zamówieniu: automatycznie wydawaj osobiste kupony, wysyłaj e-maile i przypomnienia oraz śledź wynik w jednym lejku.**

---

## 3. Pełny opis

# Odzyskaj klienta osobistym kuponem

Po zakończeniu zamówienia wyślij klientowi osobisty kupon na kolejne zakupy — rabat procentowy, kwotę stałą lub darmową dostawę.

**Next Order Discount** tworzy osobisty kupon, gdy zamówienie spełnia zadane warunki, i wysyła klientowi e-mail z kodem oraz zasadami jego wykorzystania. Jeśli kupon pozostaje niewykorzystany, moduł może wysłać do dwóch automatycznych przypomnień. Reguły wydawania, e-maile i statystyki są dostępne w panelu administracyjnym PrestaShop.

## Jak to działa

1. Tworzysz regułę i wybierasz, które zamówienia biorą udział w kampanii.
2. Gdy zamówienie spełnia wszystkie warunki i osiąga wymagany status, moduł tworzy osobisty kupon i od razu wysyła klientowi e-mail z kuponem.
3. Moduł automatycznie wysyła zaplanowane przypomnienia, ponawia nieudane wysyłki i aktualizuje statusy kuponów.
4. Na pulpicie widzisz drogę kuponów od utworzenia do wykorzystania.

Po wstępnej konfiguracji kampania działa automatycznie, a wyniki pozostają pod Twoją kontrolą.

## Twórz różne oferty do różnych celów

Zamiast jednego rabatu dla wszystkich twórz osobne oferty dla nowych klientów, stałych klientów, dużych zamówień, wybranych krajów lub wybranych kategorii produktów. Każda reguła określa, kto otrzyma kupon, jaką korzyść zobaczy i jak długo będzie mógł z niej skorzystać.

Dla każdej reguły dostępne są trzy rodzaje korzyści:

- rabat procentowy;
- stała kwota rabatu;
- darmowa dostawa.

Okres ważności kupona i minimalną kwotę następnego zamówienia ustawia się osobno.

Przykładowe scenariusze:

- **10% po pierwszym zamówieniu** — łagodnie zachęcić nowego klienta do drugiego zakupu;
- **15 € rabatu po zamówieniu od 120 €** — nagrodzić klientów z wysoką wartością koszyka;
- **darmowa dostawa dla stałych klientów** — zaoferować przywilej wybranej grupie;
- **sezonowy kupon na wybrane marki lub kategorie** — wesprzeć kampanię celowaną bez ogólnej wyprzedaży.

## Wydawaj kupon tylko właściwym klientom

Wszystkie warunki w regule sprawdzane są jednocześnie. Można ustawić:

- statusy zamówienia, przy których tworzony jest kupon;
- minimalną i maksymalną wartość zamówienia źródłowego;
- okres trwania kampanii;
- numer kolejnego zamówienia klienta — na przykład tylko pierwsze zamówienie albo począwszy od trzeciego;
- grupy klientów;
- kraje;
- waluty;
- kategorie produktów;
- marki.

Dla warunków listowych dostępne są tryby „Wszystkie”, „Tylko wybrane” i „Wszystkie oprócz wybranych”.

Reguły sprawdzane są według priorytetu. Opcja zatrzymania dalszego sprawdzania pozwala zdecydować, czy klient otrzyma tylko jeden kupon, czy kilka kuponów z różnych pasujących reguł.

## Dostosuj każdy e-mail do kampanii

Każda reguła ma własny zestaw wiadomości:

- główny e-mail z kuponem;
- pierwsze przypomnienie;
- drugie przypomnienie.

Temat i treść HTML można ustawić osobno dla każdego języka sklepu. Znaczniki podstawiają kod i wysokość rabatu, termin ważności, minimalną kwotę zamówienia, imię klienta, nazwę sklepu i inne dane.

Przed startem kampanii otwórz podgląd wiadomości i wyślij kopię testową na własny adres.

## Przypomnij o niewykorzystanym kuponie

Skonfiguruj jedno lub dwa przypomnienia i wybierz sposób liczenia daty:

- po zadanej liczbie dni od wysłania głównego e-maila;
- na zadaną liczbę dni przed końcem ważności kupona.

Jeśli kupon został już wykorzystany, wygasł lub został anulowany, przypomnienia nie są dalej wysyłane.

## Zarządzaj formatem kodu rabatowego

Dla każdej reguły można ustawić:

- długość losowej części kodu;
- zestaw znaków — litery, cyfry lub ich połączenie;
- szablon ze zmienną `%key%`.

Na przykład szablon `RETURN-%key%` może utworzyć kod `RETURN-AB12CD8X`.

## Kontroluj wynik w jednym lejku

Pulpit pokazuje liczbę kuponów na poszczególnych etapach:

**utworzone → wysłane → z przypomnieniem → wykorzystane → wygasłe → anulowane**.

Tutaj też obliczana jest konwersja z kuponów utworzonych na wykorzystane oraz wyświetlany jest stan kolejki ponownych wysyłek i przypomnień. Pomaga to ocenić faktyczne wykorzystanie oferty i w porę zauważyć problem z wysyłką.

## Zautomatyzuj zadania w tle

Harmonogram cron odpowiada za planowanie i wysyłkę przypomnień, ponowne próby po nieudanej wysyłce głównego e-maila oraz przenoszenie przeterminowanych kuponów do odpowiedniego statusu.

W zakładce narzędzi można:

- zainstalować zadanie cron automatycznie, jeśli serwer to obsługuje;
- skopiować gotowe polecenie dla harmonogramu systemowego lub zewnętrznego;
- uruchomić wszystkie zadania ręcznie w celu sprawdzenia;
- sprawdzić czas ostatniego uruchomienia i stan każdego zadania;
- skontrolować kolejkę ponownych wysyłek i przypomnień.

## Zabezpiecz kampanię przed błędami i zbędnymi rabatami

- Dla jednego zamówienia źródłowego i jednej reguły kupon tworzony jest tylko raz.
- Przy anulowaniu lub zwrocie zamówienia źródłowego powiązany kupon można automatycznie dezaktywować.
- Losowe kody są sprawdzane pod kątem unikalności.
- Dziennik zdarzeń pomaga znajdować błędy i kontrolować operacje w tle.
- Okres przechowywania dziennika jest konfigurowalny.

## Co zyskuje sklep

- gotowy scenariusz odzyskiwania klienta po zakończonym zamówieniu;
- osobiste oferty zamiast jednego rabatu dla wszystkich;
- osobiste kupony oparte na standardowych regułach koszyka PrestaShop;
- konfigurowalne e-maile i do dwóch przypomnień;
- kontrolę okresu ważności i automatyczne anulowanie nieaktualnych kuponów;
- lejek wykorzystania kuponów i dziennik pracy modułu;
- obsługę wielu sklepów oraz e-maile w językach sklepu.

---

## 4. Główne funkcje

### Reguły i rabaty

- nieograniczona liczba reguł;
- priorytet reguł i zatrzymanie dalszego sprawdzania;
- rabat procentowy, kwota stała lub darmowa dostawa;
- okres ważności i minimalna kwota następnego zamówienia dla każdej reguły;
- osobisty kupon przypisany do klienta;
- zabezpieczenie przed ponownym wydaniem dla tego samego zamówienia i reguły;
- konfigurowalny format kodu kupona.

### Warunki wydania

- wybrane statusy zamówienia;
- zakres wartości zamówienia źródłowego;
- okres aktywności reguły;
- numer kolejnego zamówienia klienta;
- grupy klientów, kraje i waluty;
- kategorie produktów i marki;
- tryby włączania i wykluczania dla warunków listowych.

### E-maile i przypomnienia

- osobny główny e-mail dla każdej reguły;
- do dwóch e-maili z przypomnieniem;
- treści wiadomości dla każdego języka sklepu;
- dynamiczne znaczniki;
- podgląd wiadomości;
- wysyłka testowa;
- automatyczne zatrzymanie przypomnień po wykorzystaniu, anulowaniu lub wygaśnięciu kupona.

### Kontrola i automatyzacja

- lejek statusów kuponów i konwersja wykorzystania;
- lista wydanych kuponów z filtrami;
- ręczna ponowna wysyłka e-maila i przypomnień;
- kolejka ponownych wysyłek i przypomnień;
- asystent konfiguracji crona;
- ręczne uruchamianie zadań i kontrola ich stanu;
- automatyczne kończenie okresu ważności kuponów;
- automatyczne anulowanie kupona przy wybranych statusach zamówienia źródłowego;
- dziennik zdarzeń z filtrami i konfigurowalnym okresem przechowywania;
- tryb debugowania;
- obsługa wielu sklepów.

---

## 5. Pytania i odpowiedzi

### Czym ten moduł różni się od zwykłego kodu rabatowego?

Zwykły kod rabatowy zazwyczaj reklamuje się wszystkim klientom przed bieżącym zakupem lub w jego trakcie. Next Order Discount tworzy osobisty kupon dla konkretnego klienta po odpowiednim zamówieniu i motywuje go, by wrócił po kolejne zakupy.

### Kiedy tworzony jest kupon?

Gdy zamówienie osiąga jeden ze statusów wybranych w regule i jednocześnie spełnia wszystkie pozostałe warunki tej reguły.

### Czy można wydawać kupon tylko po pierwszym zamówieniu?

Tak. Ustaw minimalny i maksymalny numer kolejnego zamówienia na 1. Analogicznie można skonfigurować kampanię dla drugiego, trzeciego lub kolejnych zamówień.

### Czy można zaproponować różne rabaty różnym klientom?

Tak. Utwórz kilka reguł z różnymi warunkami, rabatami i priorytetami. Można uwzględnić grupę klienta, kraj, walutę, wartość zamówienia, produkty z określonych kategorii lub marek oraz inne parametry.

### Czy można wydać kilka kuponów za jedno zamówienie?

Tak, jeśli zamówienie pasuje do kilku reguł, a w regułach nie włączono zatrzymania dalszego sprawdzania. Jeśli potrzebny jest tylko jeden kupon, ustaw priorytety i włącz zatrzymanie w wybranej regule.

### Jakie rodzaje rabatu są obsługiwane?

Rabat procentowy, kwota stała i darmowa dostawa.

### Czy można wymagać minimalnej kwoty następnego zamówienia?

Tak. Minimalna kwota realizacji ustawiana jest osobno dla każdej reguły.

### Czy moduł sam wysyła e-mail z kuponem?

Tak. Po zmianie statusu pasującego zamówienia moduł tworzy kupon i od razu wysyła e-mail przez system pocztowy PrestaShop. Jeśli wysyłka zakończy się błędem, wiadomość trafia do kolejki na ponowną próbę przez crona.

### Ile przypomnień można wysłać?

Do dwóch. Każde przypomnienie można wyłączyć, a datę wysyłki liczyć od głównego e-maila lub od daty końca ważności kupona.

### Czy przypomnienie zostanie wysłane po wykorzystaniu kupona?

Nie. Przypomnienia nie są planowane dla kuponów wykorzystanych, wygasłych ani anulowanych.

### Co się stanie przy anulowaniu lub zwrocie zamówienia źródłowego?

Jeśli nowy status zamówienia znajduje się na liście statusów anulujących, moduł dezaktywuje powiązany kupon i oznaczy go jako anulowany.

### Czy można zmienić treść wiadomości?

Tak. Dla każdej reguły i każdego języka sklepu można zmienić temat i treść HTML głównego e-maila oraz obu przypomnień. Dostępne są podgląd i wysyłka testowa.

### Czy klient zobaczy nowy blok w sklepie?

Nie. Moduł działa po złożeniu zamówienia i informuje o kuponie pocztą elektroniczną, więc nie wymaga osadzania widżetu w szablonie sklepu.

### Jak ocenić skuteczność kampanii?

Pulpit pokazuje, ile kuponów utworzono, wysłano, wykorzystano, ile wygasło i ile anulowano, a także konwersję z kuponów utworzonych na wykorzystane.

### Czy obsługiwana jest wielosklepowość?

Tak. Dane i ustawienia uwzględniają wybrany sklep, a w trybie wszystkich sklepów dostępna jest statystyka zbiorcza.

### Jakie wersje są obsługiwane?

Moduł przeznaczony jest dla PrestaShop 8.1 i nowszych. Minimalna wersja PHP to 7.2; serwer musi przy tym spełniać wymagania systemowe zainstalowanej wersji PrestaShop.

---

## 6. Frazy wyszukiwania

kupon na następne zamówienie, rabat na następne zamówienie, kod rabatowy po zakupie, automatyczny kupon, osobisty kupon, ponowny zakup, sprzedaż powtarzalna, odzyskiwanie klientów, utrzymanie klienta, rabat po zamówieniu, e-mail z kuponem, przypomnienie o kuponie, automatyzacja rabatów, reguły rabatowe PrestaShop, kupon PrestaShop, program lojalnościowy PrestaShop, darmowa dostawa na następne zamówienie, konwersja kuponów

---

## 7. Tekst do sekcji „Co nowego”

**Wersja 1.0.0 — pierwsze wydanie**

- Automatyczne tworzenie osobistego kupona po przejściu zamówienia do wybranego statusu.
- Nieograniczona liczba reguł z priorytetem i elastycznymi warunkami.
- Rabat procentowy, kwota stała lub darmowa dostawa.
- Targetowanie według wartości i numeru zamówienia, okresu kampanii, grup, krajów, walut, kategorii i marek.
- Własne e-maile dla każdej reguły i każdego języka sklepu.
- Do dwóch automatycznych przypomnień.
- Konfigurowalny format kodu kupona.
- Automatyczne anulowanie i obsługa wygaśnięcia ważności.
- Lejek kuponów, kolejka ponownych wysyłek i przypomnień, narzędzia cron oraz dziennik zdarzeń.
- Obsługa wielu sklepów.

---

## 8. Zalecane akcenty w karcie produktu

Pierwszy ekran karty powinien odpowiadać na trzy pytania:

1. **Co robi moduł?** Tworzy osobisty kupon po zamówieniu.
2. **Po co jest potrzebny?** Daje klientowi konkretny powód, by wrócił.
3. **Dlaczego to wygodne rozwiązanie?** Reguły, e-maile, przypomnienia i kontrola zebrane są w jednym module.

Zalecany nagłówek pierwszego ekranu:

> **Zamień zakończone zamówienie w powód do kolejnego zakupu**

Zalecany podtytuł:

> **Twórz osobiste kupony po zakupie, przypominaj klientom o korzyści i śledź wynik w jednym lejku.**

Na zrzutach ekranu w listingu najlepiej pokazać:

1. pulpit z lejkiem kuponów;
2. listę reguł i priorytety;
3. warunki targetowania;
4. ustawienia rabatu i okresu ważności;
5. edytor i podgląd wiadomości;
6. listę wydanych kuponów;
7. stan crona oraz kolejki ponownych wysyłek i przypomnień.

Na każdym obrazie używaj jednej krótkiej tezy, a nie wyliczenia wszystkich funkcji. Główna linia całej karty: **„Osobisty powód, by wrócić — automatycznie po każdym pasującym zamówieniu”**.
