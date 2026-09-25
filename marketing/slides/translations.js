/* Тексты 14 маркетинговых слайдов Next Order Discount.
   Русский — источник; en / fr / de / pl / es переведены с него.
   Формат блока слайда:
     баннер (1, 6, 9, 12): [заголовок, подзаголовок, [[подзаголовок пары, текст], …]]
     экран модуля:         [категория (не выводится), заголовок, пояснение]
   Длину держите близкой к русской: артборд 2000×2000 фиксированный,
   заголовок влезает в 2 строки, пояснение — в 3. */
(function () {
  'use strict';

  window.NOD_SLIDE_TRANSLATIONS = {
    ru: {
      1: [
        'Возвращайте покупателей за следующим заказом',
        'Модуль автоматически создаёт персональный купон после покупки и отправляет его клиенту по email.',
        [
          ['Купон появляется в нужный момент', 'Он создаётся после выбранного статуса заказа и действует только на следующую покупку.'],
          ['Повторные продажи под контролем', 'Воронка показывает, сколько купонов отправлено и сколько клиентов использовали предложение.']
        ]
      ],
      2: [
        'Аналитика',
        'Повторные покупки в цифрах',
        'Воронка показывает путь купона от создания до использования и помогает оценить результат каждой кампании.'
      ],
      3: [
        'Купоны',
        'Каждый купон остаётся под контролем',
        'Фильтруйте купоны по статусу, повторно отправляйте письмо и запускайте напоминания из общей таблицы.'
      ],
      4: [
        'Правила',
        'Своя причина вернуться для каждого клиента',
        'Предлагайте разные скидки новым покупателям, постоянным клиентам и заказам с высоким чеком.'
      ],
      5: [
        'Механика скидки',
        'Размер скидки под вашим контролем',
        'Выберите процент, фиксированную сумму или бесплатную доставку. Ограничьте срок купона и сумму следующего заказа.'
      ],
      6: [
        'Персональные скидки по вашим правилам',
        'Модуль выдаёт купон только тем покупателям, которые соответствуют условиям кампании.',
        [
          ['Больше вариантов сегментации', 'Учитывайте сумму заказа, страну, валюту, категорию, бренд и историю покупок.'],
          ['Защита от лишних скидок', 'Приоритеты правил помогают выбрать одно лучшее предложение или выдать несколько совместимых купонов.']
        ]
      ],
      7: [
        'Условия',
        'Кампания для конкретной аудитории',
        'Соберите условия в одном правиле и запускайте предложения для нужных стран, групп клиентов, категорий или брендов.'
      ],
      8: [
        'Промокод',
        'Промокод в стиле вашей кампании',
        'Настройте длину, символы и шаблон кода. Покупатель получит понятный купон, связанный с вашим предложением.'
      ],
      9: [
        'Письмо, которое возвращает клиента',
        'Каждое правило получает собственное письмо с купоном и до двух напоминаний.',
        [
          ['Персональное предложение', 'В письмо автоматически подставляются имя клиента, размер скидки, код и срок действия.'],
          ['Напоминания только для активных купонов', 'После использования, отмены или окончания купона цепочка останавливается.']
        ]
      ],
      10: [
        'Письма',
        'Письма под каждую кампанию',
        'Меняйте тему и HTML для каждого языка магазина. Плейсхолдеры автоматически добавят данные клиента и купона.'
      ],
      11: [
        'Предпросмотр',
        'Предпросмотр письма перед отправкой',
        'Предпросмотр показывает, как клиент увидит скидку, промокод, срок действия и условия применения.'
      ],
      12: [
        'Кампания работает после первой настройки',
        'Cron отправляет напоминания, повторяет неудачные письма и закрывает просроченные купоны.',
        [
          ['Меньше ручной работы', 'Одна задача cron запускает весь фоновый сценарий по расписанию.'],
          ['Письма не пропадают после ошибки', 'Неудачная отправка остаётся в очереди и повторяется автоматически.']
        ]
      ],
      13: [
        'Cron',
        'Автоматизация без сложной настройки',
        'Скопируйте готовую команду cron или запустите задачи из модуля. Состояние фоновых процессов видно на том же экране.'
      ],
      14: [
        'Настройки',
        'Скидки без неприятных сюрпризов',
        'Модуль отменяет купон при возврате исходного заказа и хранит журнал событий для проверки кампании.'
      ]
    },

    en: {
      1: [
        'Bring customers back for their next order',
        'The module automatically creates a personal coupon after a purchase and emails it to the customer.',
        [
          ['The coupon arrives at the right moment', 'It is created after the chosen order status and applies only to the next purchase.'],
          ['Repeat sales under control', 'The funnel shows how many coupons went out and how many customers used the offer.']
        ]
      ],
      2: [
        'Analytics',
        'Repeat purchases in numbers',
        'The funnel shows the coupon path from creation to use and helps you measure every campaign.'
      ],
      3: [
        'Coupons',
        'Every coupon stays under control',
        'Filter coupons by status, resend the email and trigger reminders from a single table.'
      ],
      4: [
        'Rules',
        'A reason to return for every customer',
        'Offer different discounts to new customers, regulars and high-value orders.'
      ],
      5: [
        'Discount mechanics',
        'You set the size of the discount',
        'Choose a percentage, a fixed amount or free shipping. Cap the validity and the next order total.'
      ],
      6: [
        'Personal discounts on your own rules',
        'The module issues a coupon only to the customers who match the campaign conditions.',
        [
          ['More ways to segment', 'Take order total, country, currency, category, brand and purchase history into account.'],
          ['Protection against extra discounts', 'Rule priorities pick one best offer or let several compatible coupons through.']
        ]
      ],
      7: [
        'Conditions',
        'A campaign for a specific audience',
        'Collect the conditions in one rule and target the countries, customer groups, categories or brands you need.'
      ],
      8: [
        'Coupon code',
        'A code in the style of your campaign',
        'Set the length, characters and template. The customer gets a clear code tied to your offer.'
      ],
      9: [
        'The email that brings the customer back',
        'Every rule gets its own coupon email and up to two reminders.',
        [
          ['A personal offer', 'Customer name, discount value, code and expiry date are inserted automatically.'],
          ['Reminders for active coupons only', 'Once the coupon is used, canceled or expired, the chain stops.']
        ]
      ],
      10: [
        'Emails',
        'An email for every campaign',
        'Change the subject and HTML per shop language. Placeholders add the customer and coupon data.'
      ],
      11: [
        'Preview',
        'Preview the email before sending',
        'You see the discount, code, expiry date and terms exactly as the customer will.'
      ],
      12: [
        'The campaign runs after one setup',
        'Cron sends reminders, retries failed emails and closes expired coupons.',
        [
          ['Less manual work', 'A single cron task drives the whole background scenario on schedule.'],
          ['No email is lost after an error', 'A failed send stays in the queue and is retried automatically.']
        ]
      ],
      13: [
        'Cron',
        'Automation without complex setup',
        'Copy the ready-made cron command or run the tasks from the module. The state is visible on the same screen.'
      ],
      14: [
        'Settings',
        'Discounts without nasty surprises',
        'The module cancels the coupon if the source order is refunded and keeps an event log for checks.'
      ]
    },

    fr: {
      1: [
        'Ramenez vos clients pour leur prochaine commande',
        "Le module crée automatiquement un bon personnel après l'achat et l'envoie au client par e-mail.",
        [
          ['Le bon arrive au bon moment', "Il est créé après le statut de commande choisi et ne vaut que pour l'achat suivant."],
          ['Les ventes répétées sous contrôle', "L'entonnoir montre combien de bons sont partis et combien de clients en ont profité."]
        ]
      ],
      2: [
        'Analyse',
        'Le réachat en chiffres',
        "L'entonnoir montre le parcours du bon, de sa création à son utilisation, et mesure chaque campagne."
      ],
      3: [
        'Bons',
        'Chaque bon reste sous contrôle',
        "Filtrez par statut, renvoyez l'e-mail et lancez les rappels depuis un seul tableau."
      ],
      4: [
        'Règles',
        'Une raison de revenir pour chaque client',
        'Proposez des remises différentes aux nouveaux clients, aux fidèles et aux gros paniers.'
      ],
      5: [
        'Mécanique de la remise',
        'La remise reste sous votre contrôle',
        'Choisissez un pourcentage, un montant fixe ou la livraison gratuite. Fixez la validité et le montant minimum.'
      ],
      6: [
        'Des remises personnelles selon vos règles',
        "Le module n'émet un bon que pour les clients qui remplissent les conditions de la campagne.",
        [
          ['Plus de critères de ciblage', "Tenez compte du montant, du pays, de la devise, de la catégorie, de la marque et de l'historique."],
          ['Protection contre les remises en trop', 'Les priorités choisissent la meilleure offre ou laissent passer plusieurs bons compatibles.']
        ]
      ],
      7: [
        'Conditions',
        'Une campagne pour un public précis',
        'Réunissez les conditions dans une règle et ciblez pays, groupes de clients, catégories ou marques.'
      ],
      8: [
        'Code promo',
        'Un code promo aux couleurs de la campagne',
        'Réglez la longueur, les caractères et le modèle. Le client reçoit un code clair, lié à votre offre.'
      ],
      9: [
        "L'e-mail qui fait revenir le client",
        'Chaque règle dispose de son e-mail de bon et de deux rappels au maximum.',
        [
          ['Une offre personnelle', "Le nom du client, le montant de la remise, le code et la date d'expiration sont insérés automatiquement."],
          ['Des rappels pour les bons actifs seulement', "Dès que le bon est utilisé, annulé ou expiré, la série de rappels s'arrête."]
        ]
      ],
      10: [
        'E-mails',
        'Un e-mail pour chaque campagne',
        "Modifiez l'objet et le HTML pour chaque langue. Les variables ajoutent les données du client et du bon."
      ],
      11: [
        'Aperçu',
        "Un aperçu de l'e-mail avant l'envoi",
        'Vous voyez la remise, le code, la date de validité et les conditions exactement comme le client.'
      ],
      12: [
        'La campagne tourne après un seul réglage',
        'Le cron envoie les rappels, relance les e-mails échoués et clôture les bons expirés.',
        [
          ['Moins de travail manuel', "Une seule tâche cron déclenche tout le scénario de fond, selon l'horaire prévu."],
          ['Aucun e-mail perdu après une erreur', "Un envoi échoué reste en file d'attente et se répète automatiquement."]
        ]
      ],
      13: [
        'Cron',
        'Une automatisation sans réglage complexe',
        "Copiez la commande prête à l'emploi ou lancez les tâches depuis le module. L'état est visible ici."
      ],
      14: [
        'Paramètres',
        'Des remises sans mauvaise surprise',
        'Le module annule le bon si la commande est remboursée et garde un journal pour vérifier la campagne.'
      ]
    },

    de: {
      1: [
        'Holen Sie Kunden für die nächste Bestellung zurück',
        'Das Modul erstellt nach dem Kauf automatisch einen persönlichen Gutschein und sendet ihn per E-Mail.',
        [
          ['Der Gutschein kommt im richtigen Moment', 'Er entsteht nach dem gewählten Bestellstatus und gilt nur für den nächsten Einkauf.'],
          ['Folgekäufe unter Kontrolle', 'Der Funnel zeigt, wie viele Gutscheine versendet und wie viele eingelöst wurden.']
        ]
      ],
      2: [
        'Analyse',
        'Folgekäufe in Zahlen',
        'Der Funnel zeigt den Weg des Gutscheins von der Erstellung bis zur Einlösung und misst jede Kampagne.'
      ],
      3: [
        'Gutscheine',
        'Jeder Gutschein bleibt unter Kontrolle',
        'Filtern Sie nach Status, senden Sie die E-Mail erneut und starten Sie Erinnerungen aus einer Tabelle.'
      ],
      4: [
        'Regeln',
        'Für jeden Kunden ein eigener Grund',
        'Bieten Sie Neukunden, Stammkunden und großen Warenkörben unterschiedliche Rabatte.'
      ],
      5: [
        'Rabattmechanik',
        'Sie bestimmen die Rabatthöhe',
        'Wählen Sie Prozent, festen Betrag oder kostenlosen Versand. Legen Sie Gültigkeit und Mindestbetrag fest.'
      ],
      6: [
        'Persönliche Rabatte nach Ihren Regeln',
        'Das Modul gibt einen Gutschein nur an Kunden aus, die die Bedingungen der Kampagne erfüllen.',
        [
          ['Mehr Möglichkeiten zur Segmentierung', 'Berücksichtigen Sie Betrag, Land, Währung, Kategorie, Marke und Kaufhistorie.'],
          ['Schutz vor unnötigen Rabatten', 'Prioritäten wählen das beste Angebot aus oder lassen mehrere passende Gutscheine zu.']
        ]
      ],
      7: [
        'Bedingungen',
        'Eine Kampagne für ein klares Publikum',
        'Bündeln Sie die Bedingungen in einer Regel und zielen Sie auf Länder, Gruppen, Kategorien oder Marken.'
      ],
      8: [
        'Gutscheincode',
        'Ein Code im Stil Ihrer Kampagne',
        'Stellen Sie Länge, Zeichen und Vorlage ein. Der Kunde erhält einen klaren Code zu Ihrem Angebot.'
      ],
      9: [
        'Die E-Mail, die den Kunden zurückholt',
        'Jede Regel hat ihre eigene Gutschein-E-Mail und bis zu zwei Erinnerungen.',
        [
          ['Ein persönliches Angebot', 'Name des Kunden, Rabatthöhe, Code und Ablaufdatum werden automatisch eingesetzt.'],
          ['Erinnerungen nur für aktive Gutscheine', 'Sobald der Gutschein eingelöst, storniert oder abgelaufen ist, endet die Kette.']
        ]
      ],
      10: [
        'E-Mails',
        'Eine E-Mail für jede Kampagne',
        'Ändern Sie Betreff und HTML je Sprache. Platzhalter ergänzen die Daten von Kunde und Gutschein.'
      ],
      11: [
        'Vorschau',
        'Vorschau der E-Mail vor dem Versand',
        'Sie sehen Rabatt, Code, Gültigkeit und Bedingungen genau so, wie der Kunde sie sieht.'
      ],
      12: [
        'Die Kampagne läuft nach einmaliger Einrichtung',
        'Der Cron sendet Erinnerungen, wiederholt fehlgeschlagene E-Mails und schließt abgelaufene Gutscheine.',
        [
          ['Weniger Handarbeit', 'Eine einzige Cron-Aufgabe stößt den gesamten Hintergrundablauf planmäßig an.'],
          ['Keine E-Mail geht nach einem Fehler verloren', 'Ein fehlgeschlagener Versand bleibt in der Warteschlange und wird automatisch wiederholt.']
        ]
      ],
      13: [
        'Cron',
        'Automatisierung ohne komplizierte Einrichtung',
        'Kopieren Sie den fertigen Cron-Befehl oder starten Sie die Aufgaben im Modul. Der Zustand ist hier sichtbar.'
      ],
      14: [
        'Einstellungen',
        'Rabatte ohne böse Überraschungen',
        'Das Modul storniert den Gutschein bei einer Rückerstattung und führt ein Protokoll zur Kontrolle.'
      ]
    },

    pl: {
      1: [
        'Odzyskuj klientów przy następnym zamówieniu',
        'Moduł automatycznie tworzy osobisty kupon po zakupie i wysyła go klientowi e-mailem.',
        [
          ['Kupon pojawia się w odpowiednim momencie', 'Powstaje po wybranym statusie zamówienia i działa tylko na kolejny zakup.'],
          ['Sprzedaż powtarzalna pod kontrolą', 'Lejek pokazuje, ile kuponów wysłano i ilu klientów skorzystało z oferty.']
        ]
      ],
      2: [
        'Analityka',
        'Ponowne zakupy w liczbach',
        'Lejek pokazuje drogę kupona od utworzenia do wykorzystania i pomaga ocenić każdą kampanię.'
      ],
      3: [
        'Kupony',
        'Każdy kupon pozostaje pod kontrolą',
        'Filtruj kupony po statusie, wysyłaj wiadomość ponownie i uruchamiaj przypomnienia z jednej tabeli.'
      ],
      4: [
        'Reguły',
        'Własny powód powrotu dla każdego klienta',
        'Proponuj różne rabaty nowym klientom, stałym klientom i zamówieniom o wysokiej wartości.'
      ],
      5: [
        'Mechanika rabatu',
        'Wysokość rabatu pod Twoją kontrolą',
        'Wybierz procent, kwotę stałą lub darmową dostawę. Ogranicz ważność kupona i kwotę następnego zamówienia.'
      ],
      6: [
        'Osobiste rabaty według Twoich reguł',
        'Moduł wydaje kupon tylko tym klientom, którzy spełniają warunki kampanii.',
        [
          ['Więcej opcji segmentacji', 'Uwzględniaj wartość zamówienia, kraj, walutę, kategorię, markę i historię zakupów.'],
          ['Ochrona przed zbędnymi rabatami', 'Priorytety reguł wybierają jedną najlepszą ofertę albo dopuszczają kilka zgodnych kuponów.']
        ]
      ],
      7: [
        'Warunki',
        'Kampania dla konkretnej grupy',
        'Zbierz warunki w jednej regule i kieruj ofertę do wybranych krajów, grup klientów, kategorii lub marek.'
      ],
      8: [
        'Kod rabatowy',
        'Kod w stylu Twojej kampanii',
        'Ustaw długość, znaki i szablon kodu. Klient dostanie czytelny kupon powiązany z Twoją ofertą.'
      ],
      9: [
        'E-mail, który sprowadza klienta z powrotem',
        'Każda reguła ma własną wiadomość z kuponem i do dwóch przypomnień.',
        [
          ['Osobista oferta', 'Imię klienta, wysokość rabatu, kod i termin ważności wstawiane są automatycznie.'],
          ['Przypomnienia tylko dla aktywnych kuponów', 'Po wykorzystaniu, anulowaniu lub wygaśnięciu kupona łańcuch się zatrzymuje.']
        ]
      ],
      10: [
        'Wiadomości',
        'Wiadomość pod każdą kampanię',
        'Zmieniaj temat i HTML dla każdego języka sklepu. Znaczniki same dodadzą dane klienta i kupona.'
      ],
      11: [
        'Podgląd',
        'Podgląd wiadomości przed wysyłką',
        'Widzisz rabat, kod, termin ważności i warunki dokładnie tak, jak zobaczy je klient.'
      ],
      12: [
        'Kampania działa po jednej konfiguracji',
        'Cron wysyła przypomnienia, ponawia nieudane wiadomości i zamyka przeterminowane kupony.',
        [
          ['Mniej pracy ręcznej', 'Jedno zadanie cron uruchamia cały scenariusz w tle zgodnie z harmonogramem.'],
          ['Wiadomości nie giną po błędzie', 'Nieudana wysyłka zostaje w kolejce i jest powtarzana automatycznie.']
        ]
      ],
      13: [
        'Cron',
        'Automatyzacja bez skomplikowanej konfiguracji',
        'Skopiuj gotowe polecenie cron albo uruchom zadania z modułu. Stan procesów widać na tym samym ekranie.'
      ],
      14: [
        'Ustawienia',
        'Rabaty bez przykrych niespodzianek',
        'Moduł anuluje kupon przy zwrocie zamówienia źródłowego i prowadzi dziennik zdarzeń do kontroli.'
      ]
    },

    es: {
      1: [
        'Recupere clientes para su próximo pedido',
        'El módulo crea automáticamente un cupón personal tras la compra y lo envía al cliente por correo.',
        [
          ['El cupón llega en el momento justo', 'Se crea tras el estado de pedido elegido y solo sirve para la compra siguiente.'],
          ['Las ventas recurrentes bajo control', 'El embudo muestra cuántos cupones se enviaron y cuántos clientes los usaron.']
        ]
      ],
      2: [
        'Analítica',
        'La recompra en cifras',
        'El embudo muestra el recorrido del cupón, de su creación a su uso, y mide cada campaña.'
      ],
      3: [
        'Cupones',
        'Cada cupón sigue bajo control',
        'Filtre por estado, reenvíe el correo y lance los recordatorios desde una sola tabla.'
      ],
      4: [
        'Reglas',
        'Un motivo para volver para cada cliente',
        'Ofrezca descuentos distintos a clientes nuevos, a los habituales y a los pedidos de ticket alto.'
      ],
      5: [
        'Mecánica del descuento',
        'El descuento, bajo su control',
        'Elija porcentaje, importe fijo o envío gratuito. Fije la validez y el importe del próximo pedido.'
      ],
      6: [
        'Descuentos personales según sus reglas',
        'El módulo emite un cupón solo a los clientes que cumplen las condiciones de la campaña.',
        [
          ['Más opciones de segmentación', 'Tenga en cuenta el importe, el país, la moneda, la categoría, la marca y el historial.'],
          ['Protección frente a descuentos de más', 'Las prioridades eligen la mejor oferta o permiten varios cupones compatibles.']
        ]
      ],
      7: [
        'Condiciones',
        'Una campaña para un público concreto',
        'Reúna las condiciones en una regla y dirija la oferta a países, grupos, categorías o marcas.'
      ],
      8: [
        'Código promocional',
        'Un código con el estilo de su campaña',
        'Ajuste la longitud, los caracteres y la plantilla. El cliente recibe un código claro y reconocible.'
      ],
      9: [
        'El correo que hace volver al cliente',
        'Cada regla tiene su propio correo con el cupón y hasta dos recordatorios.',
        [
          ['Una oferta personal', 'El nombre del cliente, el descuento, el código y la caducidad se insertan solos.'],
          ['Recordatorios solo para cupones activos', 'Cuando el cupón se usa, se cancela o caduca, la cadena se detiene.']
        ]
      ],
      10: [
        'Correos',
        'Un correo para cada campaña',
        'Cambie el asunto y el HTML en cada idioma. Los marcadores añaden los datos del cliente y del cupón.'
      ],
      11: [
        'Vista previa',
        'Vista previa del correo antes de enviarlo',
        'Ve el descuento, el código, la validez y las condiciones igual que los verá el cliente.'
      ],
      12: [
        'La campaña funciona tras el primer ajuste',
        'El cron envía los recordatorios, reintenta los correos fallidos y cierra los cupones vencidos.',
        [
          ['Menos trabajo manual', 'Una sola tarea cron pone en marcha todo el proceso de fondo según el horario.'],
          ['Ningún correo se pierde tras un error', 'Un envío fallido permanece en la cola y se repite automáticamente.']
        ]
      ],
      13: [
        'Cron',
        'Automatización sin configuración compleja',
        'Copie el comando de cron ya preparado o lance las tareas desde el módulo. El estado se ve aquí mismo.'
      ],
      14: [
        'Configuración',
        'Descuentos sin sorpresas desagradables',
        'El módulo anula el cupón si se reembolsa el pedido y guarda un registro para revisar la campaña.'
      ]
    }
  };
})();
