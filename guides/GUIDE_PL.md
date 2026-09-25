_Wersja modułu: 1.0.0 (Next Order Discount)_

**Next Order Discount** to moduł, który automatycznie wydaje klientowi osobisty kupon na **następne zamówienie**, gdy jego bieżące zamówienie osiągnie wymagany status.

Działanie modułu konfiguruje się za pomocą **reguł**. W każdej regule wskazujesz, na jakich warunkach klient otrzyma kupon i jaki rabat będzie obowiązywał. Takich reguł można utworzyć dowolną liczbę. Gdy zamówienie spełnia warunki jednej z nich, moduł tworzy kupon i sam zarządza całym jego cyklem życia: wysyła klientowi e-mail oraz przypomnienia, pilnuje terminu ważności i anuluje kupon przy zwrocie zamówienia.

Co otrzymuje klient:

- osobisty kod kuponu na kolejne zakupy;
- e-mail z kuponem zaraz po złożeniu zamówienia;
- jedno lub dwa przypomnienia, dopóki kupon nie zostanie wykorzystany i nie wygaśnie;
- czytelny termin ważności oraz — w razie potrzeby — minimalną kwotę następnego zamówienia.

**Możliwości modułu:**

- **reguły rabatowe**: twórz tyle reguł, ile potrzebujesz, ustawiaj dla każdej własne warunki i rabat oraz zarządzaj kolejnością ich stosowania za pomocą priorytetu;
- trzy rodzaje rabatu: **procent**, **kwota stała**, **darmowa dostawa**; okres ważności i minimalna kwota następnego zamówienia — osobno dla każdej reguły;
- **elastyczne warunki** wyzwalania: statusy zamówienia, przy których wydawany jest kupon, zakres wartości zamówienia źródłowego, okno aktywności według dat, numer zamówienia klienta (na przykład „tylko pierwsze zamówienie”), wykluczenie zamówień gościnnych, a także warunki listowe w trybie **All / Include / Exclude** — grupy klientów, kraje, waluty, kategorie produktów i marki;
- flaga **„Stop after this rule”** i priorytet: albo jeden kupon na zamówienie, albo kilka kuponów z różnych reguł;
- **własny format kodu** dla każdej reguły: długość, zestaw znaków (litery / cyfry / alfanumeryczny) oraz szablon ze zmienną `%key%` (na przykład `NOD-%key%`);
- **własne e-maile dla każdej reguły**: wiadomość z kuponem i dwie wiadomości z przypomnieniem, osobno **dla każdego języka sklepu**, z podglądem, wysyłką testową i wyborem języka przy ręcznej wysyłce z listy kuponów;
- **przypomnienia**: 1. i 2. wiadomość, liczone albo od daty e-maila z kuponem, albo od daty wygaśnięcia; zatrzymują się same, gdy kupon zostanie wykorzystany lub wygaśnie;
- **automatyczne anulowanie** kupona, jeśli zamówienie źródłowe trafi do statusu anulowania lub zwrotu;
- zadania w tle wykonywane przez **cron** (kolejka wiadomości, planowanie przypomnień, wygasanie kuponów) z asystentem konfiguracji, kontrolą stanu i ręcznym uruchamianiem;
- **pulpit** — lejek kuponów (wygenerowane → wysłane → z przypomnieniem → użyte → wygasłe → anulowane), konwersja i wykres dziennej dynamiki z ostatnich 30 dni;
- **dziennik (Logs)** zdarzeń modułu z filtrami i okresem przechowywania;
- obsługa **wielu sklepów** (reguły, kupony i ustawienia w kontekście wybranego sklepu) oraz wielojęzycznych wiadomości.

**Przykład:**

- Reguła: „Rabat 10% na następne zamówienie, ważny 30 dni, dla zamówień od 100 €”.
- Klient złożył zamówienie na 150 €, a zamówienie osiągnęło status opłaconego.
**→ dla klienta utworzono osobisty kupon −10%, wysłano e-mail, a po kilku dniach przyjdzie przypomnienie, jeśli kupon nie zostanie wykorzystany.**

_Zrzut ekranu: jak klient otrzymuje kupon na następne zamówienie w wiadomości e-mail._
![img.png](img.png)



<a id="toc"></a>

## Spis treści

1. [Gdzie otworzyć moduł w panelu administracyjnym](#t1)
2. [Jakie są zakładki](#t2)
3. [Jak działa moduł (cykl życia kupona)](#t3)
4. [Zakładka Pulpit: lejek i dynamika](#t4)
5. [Zakładka Reguły: tabela reguł](#t5)
    - [Struktura tabeli](#t6)
    - [Dostępne działania](#t7)
    - [Priorytet i kolejność reguł](#t8)
6. [Tworzenie i edycja reguły (Rule)](#t9)
    - [6.1 Zakładka Ogólne (podstawowe)](#t10)
    - [6.2 Zakładka Warunki](#t11)
    - [6.3 Zakładka Kod (format kodu)](#t12)
    - [6.4 Zakładka E-mail (wiadomości)](#t13)
    - [Działania formularza](#t14)
7. [Zakładka Kupony: wydane kupony](#t15)
    - [Filtry](#t16)
    - [Kolumny tabeli](#t17)
    - [Ręczna wysyłka wiadomości i przypomnień](#t18)
8. [Zakładka Ustawienia](#t19)
9. [Zakładka Cron/Narzędzia: zadania w tle](#t20)
    - [Konfiguracja crona](#t21)
    - [Stan zadań w tle](#t22)
    - [Kolejka wysyłki](#t23)
10. [Zakładka Logi: dziennik](#t24)
11. [Wsparcie](#t25)
12. [Szybki start (konfiguracja w 5–10 minut)](#t26)
13. [Lista kontrolna diagnostyki](#t27)

---

<a id="t1"></a>

## 1. Gdzie otworzyć moduł w panelu administracyjnym

1. Zaloguj się do panelu administracyjnego PrestaShop.
2. Otwórz sekcję **Katalog**.
3. Znajdź pozycję **Next Order Discount**.

Moduł instaluje własną zakładkę w menu „Katalog” i otwiera się na swojej stronie z wewnętrznymi zakładkami (Pulpit, Reguły, Kupony, Ustawienia, Cron/Narzędzia, Logi).

_Zrzut ekranu: pozycja modułu w sekcji „Katalog”._
![img_1.png](img_1.png)

<a id="t2"></a>

## 2. Jakie są zakładki

Po otwarciu moduł domyślnie pokazuje zakładkę **Pulpit**. Dostępne zakładki:

- **Pulpit** (Dashboard) — lejek kuponów, konwersja i dzienna dynamika wydawania, wysyłki oraz wykorzystania kuponów z ostatnich 30 dni. Otwiera się domyślnie.
- **Reguły** (Rules) — zarządzanie regułami: tworzenie, edycja, włączanie/wyłączanie, priorytet, usuwanie. To właśnie tutaj ustala się rabaty i warunki ich przyznawania.
- **Kupony** (Coupons) — lista wszystkich wydanych kuponów wraz z ich regułą, klientem, statusem i terminem ważności; ręczne ponowne wysłanie wiadomości i przypomnień.
- **Ustawienia** (Settings) — ogólne ustawienia modułu: włączony/wyłączony, statusy anulowania kupona, tryb debugowania, okres przechowywania dziennika.
- **Cron/Narzędzia** (Cron/Tools) — konfiguracja zadań w tle (cron): asystent instalacji, odnośniki do zadań, kontrola stanu, ręczne uruchamianie, migawka kolejki.
- **Logi** (Logs) — dziennik zdarzeń modułu z filtrami według poziomu i kanału.

Dodatkowo otwiera się podstrona:

- **Reguła** (Rule) — formularz tworzenia/edycji pojedynczej reguły (otwierany z zakładki Reguły).

Jeśli włączona jest obsługa wielu sklepów, wszystko jest odczytywane i zapisywane **w kontekście sklepu wybranego u góry**. W kontekście „Wszystkie sklepy” listy kuponów, lejek, wykres dynamiki i kolejka pokazywane są łącznie dla wszystkich sklepów.

Na dole każdej strony modułu znajduje się blok **Potrzebujesz pomocy?** z odnośnikiem do formularza kontaktowego PrestaShop Addons.

_Zrzut ekranu: pasek wewnętrznych zakładek modułu._
![img_2.png](img_2.png)

---

<a id="t3"></a>

## 3. Jak działa moduł (cykl życia kupona)

Zrozumienie ogólnej logiki pozwala szybciej skonfigurować reguły.

1. **Wyzwalacz.** Moduł nasłuchuje zdarzeń zamówienia (walidacja zamówienia i zmiana statusu). Gdy zamówienie trafia do odpowiedniego statusu, uruchamiane jest sprawdzanie reguł.
2. **Dobór reguły.** Reguły sprawdzane są **według priorytetu** (od góry do dołu w tabeli Reguły). Dla każdej reguły weryfikowane są wszystkie jej warunki. Jeśli reguła pasuje, na jej podstawie tworzony jest kupon (osobista reguła koszyka PrestaShop przypisana do klienta).
   - Jeśli w wyzwolonej regule włączona jest flaga **Zatrzymaj po tej regule**, sprawdzanie kończy się w tym miejscu — klient otrzyma **jeden** kupon.
   - Jeśli flaga jest wyłączona, sprawdzane są także kolejne reguły, a każda pasująca może wydać **własny** kupon.
3. **Idempotentność.** Dla jednego zamówienia źródłowego kupon z danej reguły tworzony jest tylko raz — powtórne wywołania hooka nie powielają wpisów.
4. **Wiadomość.** Zaraz po utworzeniu kupona moduł próbuje wysłać e-mail do klienta. Jeśli wysyłka się nie powiedzie, wiadomość trafia do kolejki, a cron ponawia próbę (zob. [Cron/Narzędzia](#t20)). Automatyczna wysyłka używa języka zamówienia źródłowego; jeśli jest niedostępny, moduł sięga kolejno po język konta klienta i domyślny język sklepu.
5. **Przypomnienia.** Jeśli w regule włączono przypomnienia, moduł planuje 1. i 2. wiadomość. Są one wysyłane, dopóki kupon **nie zostanie wykorzystany i nie wygaśnie**.
6. **Wykorzystanie.** Gdy klient zastosuje kupon do nowego zamówienia, odpowiedni wpis zostaje oznaczony jako **used**, a przypomnienia dla niego ustają.
7. **Wygaśnięcie.** Po upływie terminu ważności kupon przechodzi do statusu **expired** (za sprawą zadania w tle).
8. **Anulowanie.** Jeśli **zamówienie źródłowe** trafi do statusu z listy anulowania (domyślnie „Anulowane” i „Zwrócone”), wydany przez nie kupon zostaje dezaktywowany i oznaczony jako **canceled**, a nowy kupon dla tego zamówienia nie jest tworzony.

Statusy kupona, które zobaczysz w module: **created** (utworzony) → **emailed** (wysłany) → **reminded** (wysłano przypomnienie) → **used** (wykorzystany) / **expired** (wygasły) / **canceled** (anulowany).

---

<a id="t4"></a>

## 4. Zakładka Pulpit: lejek i dynamika

Zakładka **Pulpit** pokazuje ogólne wyniki działania reguł oraz zmiany kluczowych wskaźników w ujęciu dziennym, w kontekście wybranego sklepu.

### Lejek kuponów (Coupon funnel)
Sześć kafelków z liczbą kuponów na każdym etapie i udziałem w kuponach wygenerowanych. Procent widnieje obok liczby oraz na kolorowym pasku pod nią; dla **Wygenerowane** procent nie jest wyświetlany, ponieważ to wartość bazowa:

- **Wygenerowane** — łączna liczba wygenerowanych kuponów (podstawa do obliczania procentów).
- **Wysłane** — dla ilu wysłano wiadomość z kuponem.
- **Przypomniane** — dla ilu wysłano co najmniej jedno przypomnienie.
- **Użyte** — wykorzystane przez klientów.
- **Wygasłe** — po terminie ważności.
- **Anulowane** — anulowane (m.in. z powodu zwrotu zamówienia źródłowego).

Pod lejkiem znajduje się **Konwersja (użyte vs wygenerowane)**: udział kuponów wykorzystanych w wygenerowanych. To kluczowy wskaźnik skuteczności akcji.

> Etapy lejka mogą się pokrywać i nie muszą sumować się do 100%: na przykład kupon użyty lub wygasły nadal liczy się wśród wcześniej wysłanych, jeśli wiadomość z kuponem została pomyślnie doręczona.

### Dynamika dzienna (Daily dynamics)

Wykres liniowy pokazuje wskaźniki dla każdego dnia z ostatnich **30 dni**, łącznie z dzisiejszym:

- **Wygenerowane** — ile kuponów utworzono tego dnia;
- **Wysłane** — ile wiadomości z kuponem wysłano pomyślnie tego dnia;
- **Użyte** — ile kuponów wykorzystano tego dnia.

Wszystkie trzy linie korzystają z jednej skali, więc można je porównywać bezpośrednio. Najedź kursorem na punkt, aby zobaczyć wartości dla danego dnia. Kliknięcie wskaźnika w legendzie ukrywa lub przywraca odpowiednią linię; skala wykresu jest wtedy automatycznie przeliczana. Jeśli w ciągu ostatnich 30 dni nie było żadnego zdarzenia, wykres nie jest pokazywany.

> Przy obsłudze wielu sklepów dane prezentowane są w kontekście sklepu wybranego u góry (w „Wszystkie sklepy” — łącznie).

_Zrzut ekranu: zakładka Pulpit (lejek i dynamika dzienna)._
![img_3.png](img_3.png)

---

<a id="t5"></a>

## 5. Zakładka Reguły: tabela reguł

To główny obszar roboczy: wymienione są tu wszystkie reguły wydawania kuponów dla bieżącego sklepu. Przycisk **Dodaj regułę** (w nagłówku strony) otwiera formularz tworzenia reguły. Po nowej instalacji moduł nie tworzy aktywnej reguły automatycznie: dopóki sam nie dodasz i nie włączysz reguły, kupony nie będą wydawane.

Jeśli reguł jeszcze nie ma, zamiast tabeli wyświetlane jest ostrzeżenie **Brak reguł rabatowych** oraz dodatkowy przycisk **Dodaj regułę** — kliknij go lub przycisk o tej samej nazwie w nagłówku strony, aby utworzyć pierwszą regułę.

<a id="t6"></a>

### Struktura tabeli

Każdy wiersz to jedna reguła. Kolumny:

- **Priorytet** — priorytet (liczba) oraz strzałki do przesuwania w górę/w dół. Reguły sprawdzane są w tej kolejności.
- **Nazwa** — wewnętrzna nazwa reguły. Obok mogą znajdować się plakietki:
  - **Stop** — włączona flaga „zatrzymaj po tej regule”;
  - plakietka z dzwonkiem i dniami (na przykład `1d · 3d`) — włączone przypomnienia wraz z ich harmonogramem.
- **Rabat** — wynikowy rabat (na przykład `10%`, `15 €`, `Darmowa dostawa`).
- **Ważność** — okres ważności kupona w dniach.
- **Statusy wyzwalające** — statusy zamówienia, przy których uruchamia się reguła (albo plakietka **Dowolny status**, jeśli nie ma ograniczeń).
- **Warunki** — zestaw plakietek aktywnych warunków (grupy, kraje, waluty, kategorie, marki, zakresy). Myślnik oznacza brak warunków.
- **Aktywna** — przełącznik włączenia reguły (Tak/Nie).
- **Akcje** — edycja i usuwanie.

_Zrzut ekranu: tabela reguł z kolumnami i plakietkami._
![img_4.png](img_4.png)

<a id="t7"></a>

### Dostępne działania

**Edytuj** — otwiera formularz edycji reguły.

**Usuń** — usuwa regułę (z potwierdzeniem). Wydane już na jej podstawie kupony pozostają w tabeli Kupony.

**Aktywna (Tak/Nie)** — szybki przełącznik bezpośrednio w wierszu: wyłączona reguła nie bierze udziału w wydawaniu kuponów.

**Strzałki priorytetu (▲ ▼)** — przesuwają regułę wyżej lub niżej w kolejności sprawdzania.

_Zrzut ekranu: przyciski Edytuj / Usuń oraz przełącznik Aktywna._
![img_5.png](img_5.png)

<a id="t8"></a>

### Priorytet i kolejność reguł

Reguły sprawdzane są **od góry do dołu** według priorytetu. Kolejność ma znaczenie, gdy:

- w kilku regułach włączono **Zatrzymaj po tej regule** — zadziała pierwsza pasująca według priorytetu, pozostałe nie będą sprawdzane;
- chcesz, aby bardziej „szczegółowa” reguła (na przykład dla grupy VIP) miała szansę zadziałać przed regułą ogólną.

Priorytety są automatycznie utrzymywane jako ciągła sekwencja od 1 do N (przestawienie strzałkami przelicza numery).

_Zrzut ekranu: przesuwanie reguły strzałkami priorytetu._
![img_6.png](img_6.png)

---

<a id="t9"></a>

## 6. Tworzenie i edycja reguły (Rule)

Formularz reguły otwiera się przyciskiem **Dodaj regułę** lub **Edytuj**. Jest podzielony na cztery zakładki: **Ogólne**, **Warunki**, **Kod**, **E-mail**. Na dole znajdują się przyciski **Zapisz** i **Anuluj** (wspólne dla wszystkich zakładek).

<a id="t10"></a>

### 6.1 Zakładka Ogólne (podstawowe)

**Nazwa reguły** (Rule name) — wewnętrzna nazwa, wyświetlana w tabeli reguł. Pole obowiązkowe.

**Nazwa kuponu** (Voucher name) — nazwa kupona, którą widzi klient. Puste = używana jest wartość domyślna „Next Order Discount”.

**Opis kuponu** (Voucher description) — opcjonalny opis zapisywany na kuponie (widoczny w panelu administracyjnym).

**Aktywna** (Active) — czy reguła jest włączona (Tak/Nie).

Blok rabatu:

- **Typ rabatu** (Discount type):
  - **Procent (%)** — procent od wartości następnego zamówienia;
  - **Kwota stała** — stała kwota (w walucie);
  - **Darmowa dostawa**.
- **Wartość rabatu** (Discount value) — wysokość rabatu. Dla procentu ograniczona do 100. Etykieta po prawej (`%` lub symbol waluty) podstawiana jest automatycznie zgodnie z wybranym typem; przy typie **Darmowa dostawa** pole jest ukrywane (wartość rabatu nie jest potrzebna).
- **Okres ważności (dni)** (Validity period) — okres ważności kupona w dniach (liczba całkowita, nie mniej niż 1).
- **Minimalna kwota następnego zamówienia** (Minimum next order amount) — minimalna wartość następnego zamówienia, przy której kupon można zastosować. `0` — bez ograniczeń.

Blok logiki wydawania:

- **Zatrzymaj po tej regule** (Stop after this rule) — jeśli Tak, po zadziałaniu tej reguły pozostałe nie są sprawdzane (klient otrzymuje jeden kupon). Jeśli Nie — inne pasujące reguły również będą mogły wydać swoje kupony.

Blok przypomnień:

- **Wyślij przypomnienia** (Send reminders) — włącz wiadomości przypominające o niewykorzystanym kuponie. Przypomnienia kończą się automatycznie, gdy kupon zostanie wykorzystany lub wygaśnie.
- **Sposób liczenia przypomnień** (Reminder timing) — od czego liczyć dni:
  - **Dni po e-mailu z kuponem** — N dni po wiadomości z kuponem;
  - **Dni przed wygaśnięciem kuponu** — N dni przed wygaśnięciem kupona.
- **Pierwsze przypomnienie (dni)** / **Drugie przypomnienie (dni)** — harmonogram 1. i 2. przypomnienia. `0` lub puste = to przypomnienie nie jest wysyłane.

_Zrzut ekranu: zakładka Ogólne formularza reguły._
![img_7.png](img_7.png)

<a id="t11"></a>

### 6.2 Zakładka Warunki

Wszystkie zadane warunki muszą być spełnione jednocześnie (logika I). Pusty warunek lub warunek `Wszystkie` niczego nie ogranicza.

**Wyzwalaj przy statusach zamówienia** (Trigger on order statuses) — statusy, w których zamówienie może wydać kupon. Reguła sprawdzana jest przy utworzeniu zamówienia oraz przy każdej zmianie jego statusu. Pusta lista oznacza, że status nie ogranicza reguły. Aby wybrać kilka statusów, przytrzymaj Ctrl/Cmd. Kupon zostanie wydany tylko wtedy, gdy zamówienie spełnia także pozostałe warunki reguły.

Warunki listowe — każdy ma tryb i listę:

- **Grupy klientów** (Customer groups);
- **Kraje** (Countries) — według adresu dostawy zamówienia;
- **Waluty** (Currencies) — waluta zamówienia;
- **Kategorie produktów** (Product categories) — kategorie produktów z zamówienia;
- **Marki** (Brands) — marki (producenci) produktów z zamówienia.

Tryb każdego z nich:

- **Wszystkie (bez ograniczeń)** — nie ograniczaj (lista jest ignorowana);
- **Tylko wybrane** — reguła działa wyłącznie dla wybranych pozycji;
- **Wszystkie oprócz wybranych** — reguła działa dla wszystkich poza wybranymi.

Sama lista wyboru pojawia się tylko w trybach **Tylko wybrane** / **Wszystkie oprócz wybranych**; w trybie **Wszystkie** jest ukryta, aby nie przeszkadzała.

Warunki zakresowe:

- **Wartość zamówienia źródłowego** (Source order total) — zakres wartości zamówienia źródłowego (**Min.** / **Maks.**). `0` = brak ograniczenia dla tej granicy.
- **Okres aktywności** (Active date window) — okno aktywności reguły (**Od** / **Do**). Oba puste = reguła jest zawsze aktywna.
- **Liczba zamówień klienta** (Customer order number) — ile zamówień musi mieć klient (**Min.** / **Maks.**). Obie wartości `1` = tylko pierwsze zamówienie. `0` = bez ograniczeń. Zamówienia jednego klienta liczone są w bieżącym sklepie po adresie e-mail, nawet jeśli PrestaShop utworzył dla tego samego adresu kilka rekordów klienta; bieżące zamówienie jest już wliczone do tej liczby.
- **Tylko zarejestrowani klienci** (Registered customers only) — jeśli **Tak**, zamówienia gościnne nie biorą udziału w regule. Jeśli **Nie**, reguła tak samo sprawdza klientów zarejestrowanych i gości. Zaleca się włączenie tej opcji dla scenariuszy „pierwsze zamówienie” i odzyskiwania klienta: przy zamówieniu gościnnym PrestaShop może za każdym razem tworzyć nowy rekord klienta, więc powracającego gościa da się wiarygodnie rozpoznać tylko po zgodności adresu e-mail.

> Warunki dotyczące kategorii i marek odnoszą się do produktów **zamówienia źródłowego**. Moduł pobiera dane produktowe tylko wtedy, gdy co najmniej jedna aktywna reguła faktycznie filtruje według kategorii lub marek — oszczędza to zasoby przy pozostałych zamówieniach.

_Zrzut ekranu: zakładka Warunki._
![img_8.png](img_8.png)

<a id="t12"></a>

### 6.3 Zakładka Kod (format kodu)

Format kodu kupona ustala się **osobno dla każdej reguły**. Dowolne pole można zostawić puste — wtedy używana jest wbudowana wartość domyślna.

- **Długość klucza** (Key length) — liczba losowych znaków w `%key%` (ograniczona do zakresu 4–32).
- **Typ klucza** (Key type) — zestaw znaków używany do generowania:
  - **Litery (A-Z)** — tylko litery;
  - **Cyfry (0-9)** — tylko cyfry;
  - **Alfanumeryczny (A-Z, 0-9)** — litery i cyfry.
- **Szablon klucza** (Key template) — szablon kodu ze zmienną `%key%`. Przykład: `NOD-%key%` → `NOD-AB12CD8X`.

_Zrzut ekranu: zakładka Kod._
![img_9.png](img_9.png)

<a id="t13"></a>

### 6.4 Zakładka E-mail (wiadomości)

Każda reguła ma **własne wiadomości**, wstępnie wypełnione domyślnym szablonem. Konfiguruje się trzy typy:

- **E-mail z kuponem** (Coupon email) — wiadomość z kuponem (wysyłana przy wydaniu kupona);
- **E-mail pierwszego przypomnienia** (First reminder email) — pierwsze przypomnienie;
- **E-mail drugiego przypomnienia** (Second reminder email) — drugie przypomnienie.

Dla każdego typu:

- przełącznik **języka** (według kodu ISO) — temat i HTML ustala się **osobno dla każdego języka** sklepu;
- pole **Temat** (Subject) — temat wiadomości;
- pole **Treść HTML** (HTML content) — treść HTML wiadomości;
- pod polem HTML wiersz **Dostępne znaczniki (kliknij, aby wstawić)**: klikalne „chipy” ze znacznikami. Kliknięcie wstawia znacznik wprost do pola HTML w miejscu kursora — wygodnie, bo nie trzeba wpisywać ich ręcznie.

Znaczniki zastępowane rzeczywistymi wartościami przy wysyłce:

- kupon: `{coupon_code}`, `{coupon_value}`, `{valid_to}`, `{minimum_amount}`;
- klient: `{customer_firstname}`, `{customer_lastname}`, `{customer_fullname}`, `{customer_title}` (zwrot grzecznościowy, na przykład „Mr”/„Mrs”; puste, jeśli płeć nie została podana), `{customer_email}`;
- sklep: `{shop_name}`, `{shop_url}`, `{shop_logo}`. Znacznik `{shop_url}` jest obsługiwany przy wysyłce, ale w razie potrzeby należy wpisać go ręcznie w treści HTML.

Dla każdego języka wiadomości używane są własne temat i HTML — teksty z innych wersji językowych nie są podstawiane. Jeśli dla wybranego języka temat lub HTML nie zostały zapisane, moduł używa wbudowanego szablonu: francuskiego dla **FR** oraz angielskiego dla **EN** i wszystkich pozostałych języków. Przed włączeniem reguły uzupełnij i zapisz wiadomości dla wszystkich języków sklepu.

Działania pod każdą wiadomością:

- **Podgląd** (Preview) — podgląd wiadomości z przykładowymi wartościami znaczników (otwiera się w oknie).
- **Wyślij testowy e-mail** (Send test email) — wysyłka kopii testowej na wskazany adres (również z przykładowymi wartościami). Wygodny sposób na sprawdzenie układu przed wysyłką produkcyjną.

_Zrzut ekranu: zakładka E-mail._
![img_10.png](img_10.png)

<a id="t14"></a>

### Działania formularza

- **Zapisz** — sprawdza i zapisuje regułę, po czym wraca do listy Reguły. W razie błędów walidacji formularz pozostaje otwarty wraz z podpowiedziami.
- **Anuluj** — wraca do listy bez zapisywania.

_Zrzut ekranu: przyciski Zapisz / Anuluj._
![img_13.png](img_13.png)

---

<a id="t15"></a>

## 7. Zakładka Kupony: wydane kupony

Zakładka **Kupony** to lista wszystkich wygenerowanych kuponów (tylko podgląd + ręczne działania związane z wysyłką).

<a id="t16"></a>

### Filtry

- **Status** — filtr według statusu kupona (created / emailed / reminded / used / expired / canceled) albo „Wszystkie statusy”.
- **Kod** — wyszukiwanie po kodzie kupona.
- Przyciski **Filtruj** i **Wyczyść**.

Lista jest stronicowana (30 pozycji na stronę).

_Zrzut ekranu: filtry zakładki Kupony._
![img_11.png](img_11.png)

<a id="t17"></a>

### Kolumny tabeli

- **Kod** — kod kupona.
- **Klient** — imię i adres e-mail klienta (albo jego identyfikator, jeśli imię jest niedostępne).
- **Zamówienie źródłowe** — numer zamówienia, na podstawie którego wydano kupon.
- **Reguła** — reguła, z której utworzono kupon.
- **Status** — bieżący status (z kolorową plakietką). Obok mogą znajdować się plakietki `1` / `2` — wskazują, które przypomnienia zostały już wysłane.
- **Ważny do** — termin ważności kupona; data i godzina wyświetlane są w formacie bieżącej lokalizacji PrestaShop.
- **Utworzono** — data i godzina utworzenia w formacie bieżącej lokalizacji PrestaShop.
- **Akcje** — działania ręczne (zob. niżej).

_Zrzut ekranu: tabela kuponów._
![img_12.png](img_12.png)

<a id="t18"></a>

### Ręczna wysyłka wiadomości i przypomnień

Dopóki kupon **można jeszcze wykorzystać** (nie jest used / expired / canceled), w kolumnie Akcje dostępne są:

- **Język wysyłanego e-maila** — wybór języka ręcznej wysyłki (pokazywany, jeśli w sklepie zainstalowano więcej niż jeden język). Domyślnie wybrany jest język zamówienia źródłowego; wybrana wartość dotyczy zarówno ponownej wysyłki kupona, jak i ręcznej wysyłki przypomnienia;
- przycisk z **kopertą** (podpowiedź **Wyślij klientowi ponownie e-mail z kuponem**) — ponowna wysyłka;
- przyciski z **dzwonkiem i numerem 1 / 2** — natychmiast wysłać odpowiednie przypomnienie (pojawiają się, jeśli w regule kupona włączono te przypomnienia).

Dla kuponów wykorzystanych, wygasłych i anulowanych działania są niedostępne — ich wiadomość i przypomnienia nie mają już sensu.

_Zrzut ekranu: przyciski ponownej wysyłki i przypomnień w wierszu kupona._
![img_13.png](img_13.png)

---

<a id="t19"></a>

## 8. Zakładka Ustawienia

Zakładka **Ustawienia** zawiera ogólne ustawienia modułu (rabaty i warunki definiuje się w regułach, nie tutaj).

- **Moduł aktywny** (Module active) — główny wyłącznik. Jeśli **Nie**, kupony dla nowych zamówień nie są wydawane, niezależnie od reguł.
- **Anuluj kupon przy tych statusach zamówienia** (Cancel coupon on order statuses) — statusy zamówienia, po przejściu do których wydany przez to zamówienie kupon zostaje **anulowany** (dezaktywowany i oznaczony jako canceled), a nowy kupon dla tego zamówienia nie jest tworzony. Domyślnie — „Anulowane” i „Zwrócone”. Puste = nigdy nie anuluj automatycznie. Kilka pozycji wybiera się z Ctrl/Cmd.
- **Tryb debugowania** (Debug mode) — szczegółowe logowanie na potrzeby diagnostyki. Na produkcji trzymaj wyłączony.
- **Przechowuj logi przez (dni)** (Keep logs for) — okres przechowywania wpisów dziennika; starsze są usuwane automatycznie (podczas crona). `0` = przechowuj bezterminowo.

Kliknij **Zapisz**, aby zastosować zmiany. Ustawienia zapisywane są w kontekście bieżącego sklepu (obsługa wielu sklepów).

_Zrzut ekranu: zakładka Ustawienia._
![img_14.png](img_14.png)

---

<a id="t20"></a>

## 9. Zakładka Cron/Narzędzia: zadania w tle

Po utworzeniu kupona moduł od razu próbuje wysłać główną wiadomość. Jeśli wysyłka się nie powiedzie, wiadomość trafia do kolejki, a **cron** ponawia próbę. Cron planuje też i wysyła wiadomości z przypomnieniami oraz przenosi kupony po terminie do statusu **expired**.

<a id="t21"></a>

### Konfiguracja crona

Zalecany sposób to dodanie **jednej linii** do crontaba serwera, która co 5 minut wywołuje po HTTP połączone zadanie. Takie rozwiązanie działa na każdym hostingu i nie zależy od wersji PHP na serwerze.

W zakładce dostępne są:

- **Instalacja jednym kliknięciem** (One-click install) — jeśli na serwerze możliwa jest automatyczna konfiguracja crontaba, przycisk **Zainstaluj cron automatycznie** doda odpowiednią linię, a **Usuń cron** ją skasuje. Linia oznaczana jest znacznikami i usuwana także przy odinstalowaniu modułu. Jeśli automatyczna instalacja jest niedostępna (na przykład `shell_exec` jest zablokowany na hostingu współdzielonym), moduł wyjaśni przyczynę i zaproponuje ręczne skopiowanie linii.
- **Linia crontab (curl / wget)** — gotowe linie do ręcznego wklejenia w crontabie.
- **Albo użyj zewnętrznej usługi cron** — adres URL dla zewnętrznych usług web-cron (na przykład cron-job.org), z interwałem 5 minut.
- **Uruchom wszystkie zadania teraz** — ręczne uruchomienie wszystkich zadań naraz (szybkie sprawdzenie, czy adres działa).
- **Twój serwer** — sonda środowiska: wersja PHP, obecność curl (CLI), dostępność shell_exec — aby podpowiedzieć działający sposób.

> **Zachowaj token w tajemnicy.** Adresy URL zadań zawierają tajny token: każdy, kto zna adres, może uruchomić odpowiadające mu zadanie.

_Zrzut ekranu: blok konfiguracji crona._
![img_15.png](img_15.png)

<a id="t22"></a>

### Stan zadań w tle

Tabela **Zadania** wymienia zadania w tle, ich zalecany harmonogram, czas ostatniego uruchomienia, indywidualny adres URL, stan blokady oraz przycisk ręcznego uruchomienia.

Zadania modułu:

- **Przetwórz kolejkę wysyłki** — ponawia nieudaną wysyłkę głównej wiadomości i wysyła zaplanowane przypomnienia. Zalecane **co 5 minut**.
- **Zaplanuj przypomnienia o kuponach** — znajduje przypomnienia, których termin nadszedł, i umieszcza je w kolejce. Zalecane **co 30 minut**.
- **Wygaś przeterminowane kupony** — przenosi przeterminowane kupony do statusu expired. Zalecane **raz dziennie**.

Kolumna **Ostatnie uruchomienie** pokazuje stan zadania: **OK** (uruchamia się na czas), **Opóźnione** (spóźnia się), **Nie działa** (dawno nie było uruchamiane), **Nigdy nie uruchomiono** (ani razu). Kolumna **Blokada** pokazuje, czy zadanie wykonuje się właśnie teraz (**W trakcie**), czy jest wolne (**Wolna**) — blokada nie pozwala, by dwa uruchomienia nałożyły się na siebie.

Osobny blok **Zarządzany cron** informuje, czy linia crona została zainstalowana przez sam moduł.

_Zrzut ekranu: tabela zadań z harmonogramem i bieżącym stanem._
![img_16.png](img_16.png)

<a id="t23"></a>

### Kolejka wysyłki

Na dole znajduje się migawka kolejki: **Oczekuje / Przetwarzanie / Zakończono / Błąd**. Rosnąca liczba pozycji „Oczekuje” lub zauważalna liczba błędów to powód, by sprawdzić cron i ustawienia poczty.

_Zrzut ekranu: migawka kolejki wysyłki._
![img_17.png](img_17.png)

---

<a id="t24"></a>

## 10. Zakładka Logi: dziennik

Zakładka **Logi** to dziennik zdarzeń modułu (wydawanie kuponów, wysyłka wiadomości, błędy hooków itd.).

- Filtr **Poziom** — poziom wpisu: debug / info / warning / error (albo „Wszystkie”).
- Filtr **Kanał** — kanał (na przykład `cron`, `queue`, `coupon`).
- Kolumny: **Data**, **Poziom** (z kolorową plakietką), **Kanał**, **Wiadomość** (wraz ze szczegółami kontekstu), **Korelacja** (identyfikator wiążący wpisy jednego zdarzenia).

Lista jest stronicowana. Szczegółowość logowania zależy od **Trybu debugowania**, a okres przechowywania od ustawienia **Przechowuj logi przez** (oba ustawienia znajdziesz w zakładce [Ustawienia](#t19)). W kontekście konkretnego sklepu pokazywane są także ogólne wpisy crona i kolejki, utworzone bez powiązania z jednym sklepem: pozwala to widzieć błędy zadań w tle nawet przy wyłączonym trybie debugowania. Wpis o wydaniu kupona zawiera `id_lang` oraz kod ISO języka, w którym zostanie wysłana automatyczna wiadomość.

_Zrzut ekranu: zakładka Logi z filtrami._
![img_18.png](img_18.png)

---

<a id="t25"></a>

## 11. Wsparcie

Na dole stron modułu znajduje się blok **Potrzebujesz pomocy?** z odnośnikiem do oficjalnego formularza kontaktowego PrestaShop Addons:

https://addons.prestashop.com/contact-form.php

Napisz do nas, jeśli:

- potrzebujesz pomocy przy początkowej konfiguracji reguł lub crona;
- kupony albo wiadomości zachowują się inaczej, niż oczekiwano;
- potrzebujesz rozbudowy lub dostosowania funkcjonalności;
- pojawiają się błędy lub niestabilne zachowanie;
- masz pomysły na ulepszenia.

_Zrzut ekranu: blok wsparcia._
![img_19.png](img_19.png)

---

<a id="t26"></a>

## 12. Szybki start (konfiguracja w 5–10 minut)

1. Otwórz zakładkę **Ustawienia** i ustaw **Moduł aktywny = Tak**. W razie potrzeby skonfiguruj **Anuluj kupon przy tych statusach zamówienia**. Kliknij **Zapisz**.
2. Skonfiguruj **cron** w zakładce **Cron/Narzędzia**: kliknij **Zainstaluj cron automatycznie** (jeśli to możliwe) albo skopiuj zalecaną linię do crontaba / zewnętrznej usługi web-cron. Kliknij **Uruchom wszystkie zadania teraz**, aby sprawdzić.
3. Utwórz regułę w zakładce **Reguły → Dodaj regułę**:
   - **Ogólne**: nazwa, typ i wysokość rabatu, okres ważności, a w razie potrzeby minimalna kwota następnego zamówienia i przypomnienia;
   - **Warunki**: statusy zamówienia, przy których wydawany jest kupon, oraz ewentualne ograniczenia (grupy, kraje, kwoty, numer zamówienia itd.); dla reguł pierwszego / kolejnego zamówienia zdecyduj, czy włączyć **Tylko zarejestrowani klienci**;
   - **Kod**: format kodu (albo zostaw domyślny);
   - **E-mail**: sprawdź treści wiadomości, skorzystaj z **Podgląd** i **Wyślij testowy e-mail**.
4. Zapisz regułę i upewnij się, że ma **Aktywna = Tak**.
5. Sprawdź regułę na zamówieniu testowym: przenieś zamówienie do jednego ze statusów wskazanych w regule i upewnij się, że w zakładce **Kupony** pojawił się kupon, a wiadomość została wysłana. Jeśli pierwsza próba się nie powiodła, uruchom zadania w tle ręcznie w zakładce **Cron/Narzędzia**.
6. Śledź wyniki w zakładce **Pulpit**: oceniaj lejek, konwersję i dzienną dynamikę z ostatnich 30 dni.

---

<a id="t27"></a>

## 13. Lista kontrolna diagnostyki

**Kupon nie jest tworzony:**

1. **Moduł aktywny = Tak** (Ustawienia).
2. Istnieje co najmniej jedna reguła z **Aktywna = Tak** (Reguły).
3. Zamówienie faktycznie przechodzi do jednego ze **Statusów wyzwalających** reguły (albo reguła przyjmuje „dowolny status”).
4. Zamówienie spełnia **wszystkie** warunki reguły: zakres wartości, okno dat, numer zamówienia klienta, typ klienta (gość czy zarejestrowany), grupy/kraje/waluty/kategorie/marki.
5. Sprawdź kolejność: jeśli reguła o wyższym priorytecie ma **Zatrzymaj po tej regule**, reguły poniżej nie są sprawdzane.
6. Zajrzyj do **Logów** (przy **Trybie debugowania = Tak** wpisów jest więcej).

**Klient nie otrzymuje wiadomości:**

1. Sprawdź konfigurację poczty sklepu; wyślij **testowy e-mail** z zakładki E-mail reguły.
2. Dla konkretnego kupona możesz wybrać odpowiedni język i kliknąć przycisk z kopertą w zakładce Kupony.
3. Jeśli pierwsza próba wysyłki zakończyła się błędem, upewnij się, że działa **cron** (Cron/Narzędzia → zadanie **Przetwórz kolejkę wysyłki**, status **OK**), i kliknij **Uruchom wszystkie zadania teraz**, aby ponowić próbę.
4. W kolejce nie powinno być zablokowanych pozycji **Oczekuje** ani rosnącej liczby **Błąd** (Cron/Narzędzia → **Kolejka wysyłki**).
5. Jeśli wiadomość przyszła w niewłaściwym języku, sprawdź tekst tego języka w zakładce **E-mail** reguły. Automatycznie moduł używa języka zamówienia; przy wysyłce ręcznej — języka wybranego obok przycisków akcji.

**Przypomnienia nie są wysyłane:**

1. Reguła ma **Wyślij przypomnienia = Tak** i ustawiony harmonogram (**Pierwsze/Drugie przypomnienie** > 0).
2. Działa zadanie **Zaplanuj przypomnienia o kuponach** (Cron/Narzędzia).
3. Kupon **można jeszcze wykorzystać** (nie jest used / expired / canceled) — dla nieaktualnych kuponów przypomnienia nie są wysyłane.

**Kupon nieoczekiwanie został anulowany:**

- Zamówienie źródłowe przeszło do statusu z listy **Anuluj kupon przy tych statusach zamówienia** (Ustawienia). Usuń ten status z listy, jeśli takie zachowanie jest niepożądane.

**Kupony nie wygasają (pozostają w starych statusach):**

- Nie działa zadanie **Wygaś przeterminowane kupony** — sprawdź cron (Cron/Narzędzia).
