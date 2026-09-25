_Version du module : 1.0.0 (Next Order Discount)_

**Next Order Discount** est un module qui attribue automatiquement au client un bon personnel valable sur sa **prochaine commande**, dès que sa commande en cours atteint le statut voulu.

Le fonctionnement du module se configure à l'aide de **règles**. Dans chaque règle, vous indiquez à quelles conditions le client recevra un bon et quelle remise s'appliquera. Vous pouvez créer autant de règles que nécessaire. Lorsqu'une commande remplit les conditions de l'une d'elles, le module crée un bon et gère lui-même tout son cycle de vie : il envoie l'e-mail au client et les rappels, surveille la date d'expiration et annule le bon en cas de remboursement de la commande.

Ce que reçoit le client :

- un code de bon personnel pour son prochain achat ;
- un e-mail contenant le bon juste après la validation de la commande ;
- un ou deux rappels tant que le bon n'est ni utilisé ni expiré ;
- une durée de validité claire et, si nécessaire, un montant minimum pour la prochaine commande.

**Fonctionnalités du module :**

- **règles de remise** : créez autant de règles que nécessaire, définissez pour chacune ses propres conditions et sa remise, et maîtrisez leur ordre d'application grâce à la priorité ;
- trois types de remise : **pourcentage**, **montant fixe**, **livraison gratuite** ; durée de validité et montant minimum de la prochaine commande définis règle par règle ;
- **conditions de déclenchement flexibles** : statuts de commande qui donnent droit au bon, plage de montant de la commande d'origine, fenêtre d'activité par dates, rang de la commande du client (par exemple « uniquement la première commande »), exclusion des commandes en mode invité, ainsi que des conditions par liste en mode **All / Include / Exclude** — groupes de clients, pays, devises, catégories de produits et marques ;
- l'option **« Stop after this rule »** et la priorité : soit un seul bon par commande, soit plusieurs bons issus de règles différentes ;
- un **format de code personnalisé** pour chaque règle : longueur, jeu de caractères (lettres / chiffres / alphanumérique) et modèle avec la variable `%key%` (par exemple `NOD-%key%`) ;
- des **e-mails propres à chaque règle** : l'e-mail du bon et deux e-mails de rappel, séparément **pour chaque langue de la boutique**, avec aperçu, envoi de test et choix de la langue lors d'un envoi manuel depuis la liste des bons ;
- des **rappels** : 1er et 2e e-mail, calculés soit à partir de la date de l'e-mail du bon, soit à partir de la date d'expiration ; ils s'arrêtent d'eux-mêmes dès que le bon est utilisé ou expiré ;
- l'**annulation automatique** du bon si la commande d'origine passe à un statut annulé ou remboursé ;
- des tâches de fond via **cron** (file d'attente des e-mails, planification des rappels, expiration des bons) avec assistant de configuration, contrôle de l'état et exécution manuelle ;
- un **tableau de bord** — entonnoir des bons (créés → envoyés → relancés → utilisés → expirés → annulés), taux de conversion et graphique d'évolution quotidienne sur les 30 derniers jours ;
- un **journal (Logs)** des événements du module avec filtres et durée de conservation ;
- la prise en charge du **multiboutique** (règles, bons et réglages dans le contexte de la boutique sélectionnée) et des e-mails multilingues.

**Exemple :**

- Règle : « Remise de 10 % sur la prochaine commande, validité 30 jours, pour les commandes à partir de 100 € ».
- Le client a passé une commande de 150 € et celle-ci a atteint le statut « paiement accepté ».
**→ un bon personnel de −10 % a été créé pour le client, l'e-mail a été envoyé et, quelques jours plus tard, un rappel arrivera si le bon n'a pas été utilisé.**

_Capture d'écran : comment le client reçoit son bon pour la prochaine commande par e-mail._
![img.png](img.png)



<a id="toc"></a>

## Sommaire

1. [Où ouvrir le module dans le back-office](#t1)
2. [Quels sont les onglets](#t2)
3. [Comment fonctionne le module (cycle de vie du bon)](#t3)
4. [Onglet Tableau de bord : entonnoir et évolution](#t4)
5. [Onglet Règles : tableau des règles](#t5)
    - [Structure du tableau](#t6)
    - [Actions disponibles](#t7)
    - [Priorité et ordre des règles](#t8)
6. [Création et modification d'une règle (Rule)](#t9)
    - [6.1 Onglet Général (principal)](#t10)
    - [6.2 Onglet Conditions](#t11)
    - [6.3 Onglet Code (format du code)](#t12)
    - [6.4 Onglet E-mail (messages)](#t13)
    - [Actions du formulaire](#t14)
7. [Onglet Bons de réduction : bons émis](#t15)
    - [Filtres](#t16)
    - [Colonnes du tableau](#t17)
    - [Envoi manuel de l'e-mail et des rappels](#t18)
8. [Onglet Paramètres](#t19)
9. [Onglet Cron/Outils : tâches de fond](#t20)
    - [Configuration du cron](#t21)
    - [État des tâches de fond](#t22)
    - [File d'envoi](#t23)
10. [Onglet Journaux : le journal](#t24)
11. [Assistance](#t25)
12. [Démarrage rapide (configuration en 5 à 10 minutes)](#t26)
13. [Check-list de diagnostic](#t27)

---

<a id="t1"></a>

## 1. Où ouvrir le module dans le back-office

1. Connectez-vous au back-office de PrestaShop.
2. Ouvrez la rubrique **Catalogue**.
3. Repérez l'entrée **Next Order Discount**.

Le module installe son propre onglet dans le menu « Catalogue » et s'ouvre sur sa page, dotée d'onglets internes (Tableau de bord, Règles, Bons de réduction, Paramètres, Cron/Outils, Journaux).

_Capture d'écran : l'entrée du module dans la rubrique « Catalogue »._
![img_1.png](img_1.png)

<a id="t2"></a>

## 2. Quels sont les onglets

À son ouverture, le module affiche par défaut l'onglet **Tableau de bord**. Les onglets disponibles sont :

- **Tableau de bord** (Dashboard) — entonnoir des bons, taux de conversion et évolution quotidienne de l'émission, de l'envoi et de l'utilisation des bons sur les 30 derniers jours. Ouvert par défaut.
- **Règles** (Rules) — gestion des règles : création, modification, activation/désactivation, priorité, suppression. C'est ici que se définissent les remises et leurs conditions d'attribution.
- **Bons de réduction** (Coupons) — liste de tous les bons émis, avec leur règle, le client, le statut et la durée de validité ; renvoi manuel de l'e-mail et des rappels.
- **Paramètres** (Settings) — réglages généraux du module : activé/désactivé, statuts d'annulation du bon, mode débogage, durée de conservation du journal.
- **Cron/Outils** (Cron/Tools) — configuration des tâches de fond (cron) : assistant d'installation, liens vers les tâches, contrôle de l'état, exécution manuelle, aperçu de la file.
- **Journaux** (Logs) — journal des événements du module avec filtres par niveau et par canal.

Une sous-page s'ouvre en complément :

- **Règle** (Rule) — le formulaire de création/modification d'une règle (s'ouvre depuis l'onglet Règles).

Si le multiboutique est activé, tout est lu et enregistré **dans le contexte de la boutique sélectionnée en haut**. Dans le contexte « Toutes les boutiques », les listes de bons, l'entonnoir, le graphique d'évolution et la file sont affichés cumulés pour toutes les boutiques.

En bas de chaque page du module figure un bloc **Besoin d'aide ?** avec un lien vers le formulaire de contact de PrestaShop Addons.

_Capture d'écran : la barre des onglets internes du module._
![img_2.png](img_2.png)

---

<a id="t3"></a>

## 3. Comment fonctionne le module (cycle de vie du bon)

Comprendre la logique générale permet de configurer les règles plus rapidement.

1. **Déclencheur.** Le module écoute les événements de commande (validation de la commande et changement de statut). Dès qu'une commande atteint un statut approprié, la vérification des règles démarre.
2. **Sélection de la règle.** Les règles sont vérifiées **par priorité** (de haut en bas dans le tableau des règles). Pour chaque règle, toutes ses conditions sont contrôlées. Si la règle correspond, un bon est créé à partir d'elle (une règle panier PrestaShop personnelle, rattachée au client).
   - Si l'option **Arrêter après cette règle** est activée sur la règle déclenchée, la vérification s'arrête là — le client recevra **un seul** bon.
   - Si l'option est désactivée, les règles suivantes sont également vérifiées et chaque règle correspondante peut émettre **son propre** bon.
3. **Idempotence.** Pour une même commande d'origine, un bon n'est créé qu'une seule fois par règle — les déclenchements répétés du hook ne produisent pas de doublons.
4. **E-mail.** Juste après la création du bon, le module tente d'envoyer l'e-mail au client. Si l'envoi échoue, l'e-mail est placé en file d'attente et le cron réessaie (voir [Cron/Outils](#t20)). L'envoi automatique utilise la langue de la commande d'origine ; si elle n'est pas disponible, le module se rabat successivement sur la langue du compte client puis sur la langue par défaut de la boutique.
5. **Rappels.** Si les rappels sont activés dans la règle, le module planifie le 1er et le 2e e-mail. Ils sont envoyés tant que le bon n'est **ni utilisé ni expiré**.
6. **Utilisation.** Lorsque le client applique le bon à une nouvelle commande, l'enregistrement correspondant passe à **used** et les rappels associés cessent.
7. **Expiration.** À la fin de la durée de validité, le bon passe au statut **expired** (par une tâche de fond).
8. **Annulation.** Si la **commande d'origine** passe à un statut figurant dans la liste d'annulation (par défaut « Annulé » et « Remboursé »), le bon qu'elle a généré est désactivé et marqué **canceled**, et aucun nouveau bon n'est créé pour cette commande.

Les statuts de bon que vous verrez dans le module : **created** (créé) → **emailed** (envoyé) → **reminded** (rappel envoyé) → **used** (utilisé) / **expired** (expiré) / **canceled** (annulé).

---

<a id="t4"></a>

## 4. Onglet Tableau de bord : entonnoir et évolution

L'onglet **Tableau de bord** présente les résultats globaux des règles et l'évolution des indicateurs clés jour par jour, dans le contexte de la boutique sélectionnée.

### Entonnoir des bons (Coupon funnel)
Six tuiles indiquant le nombre de bons à chaque étape et leur part par rapport aux bons générés. Le pourcentage apparaît à côté du chiffre et sur la barre colorée en dessous ; pour **Générés**, aucun pourcentage n'est affiché puisqu'il s'agit de la valeur de référence :

- **Générés** — nombre total de bons générés (base de calcul des pourcentages).
- **Envoyés** — nombre de bons pour lesquels l'e-mail a été envoyé.
- **Rappelés** — nombre de bons ayant reçu au moins un rappel.
- **Utilisés** — bons utilisés par les clients.
- **Expirés** — bons arrivés à expiration.
- **Annulés** — bons annulés (notamment à cause du remboursement de la commande d'origine).

Sous l'entonnoir figure la **Conversion (utilisés / générés)** : la part des bons utilisés par rapport aux bons générés. C'est l'indicateur clé de performance de l'opération.

> Les étapes de l'entonnoir peuvent se chevaucher et ne totalisent pas nécessairement 100 % : un bon utilisé ou expiré reste par exemple comptabilisé parmi les bons déjà envoyés, si l'e-mail du bon est bien parti.

### Évolution quotidienne (Daily dynamics)

Le graphique en courbes présente les indicateurs jour par jour sur les **30 derniers jours**, aujourd'hui compris :

- **Générés** — nombre de bons créés ce jour-là ;
- **Envoyés** — nombre d'e-mails de bon envoyés avec succès ce jour-là ;
- **Utilisés** — nombre de bons utilisés ce jour-là.

Les trois courbes partagent la même échelle et sont donc directement comparables. Survolez un point pour afficher les valeurs de la journée. Un clic sur un indicateur de la légende masque ou rétablit la courbe correspondante ; l'échelle du graphique est alors recalculée automatiquement. Si aucun événement n'a eu lieu au cours des 30 derniers jours, le graphique n'est pas affiché.

> En multiboutique, les données sont affichées dans le contexte de la boutique sélectionnée en haut (cumulées dans « Toutes les boutiques »).

_Capture d'écran : l'onglet Tableau de bord (entonnoir et évolution quotidienne)._
![img_3.png](img_3.png)

---

<a id="t5"></a>

## 5. Onglet Règles : tableau des règles

C'est la zone de travail principale : toutes les règles d'attribution de bons de la boutique courante y sont listées. Le bouton **Ajouter une règle** (dans l'en-tête de la page) ouvre le formulaire de création. Après une nouvelle installation, le module ne crée pas de règle active automatiquement : tant que vous n'avez pas ajouté et activé une règle vous-même, aucun bon ne sera émis.

S'il n'existe pas encore de règle, un avertissement **Aucune règle de remise pour l'instant** s'affiche à la place du tableau, accompagné d'un bouton supplémentaire **Ajouter une règle** — cliquez dessus, ou sur le bouton du même nom dans l'en-tête, pour créer votre première règle.

<a id="t6"></a>

### Structure du tableau

Chaque ligne correspond à une règle. Les colonnes sont :

- **Priorité** — la priorité (un nombre) et les flèches de déplacement vers le haut/le bas. Les règles sont vérifiées dans cet ordre.
- **Nom** — le nom interne de la règle. Des badges peuvent l'accompagner :
  - **Stop** — l'option « arrêter après cette règle » est activée ;
  - un badge avec une cloche et des jours (par exemple `1j · 3j`) — les rappels sont activés, avec leur calendrier.
- **Remise** — le résultat de la remise (par exemple `10%`, `15 €`, `Livraison gratuite`).
- **Validité** — la durée de validité du bon, en jours.
- **Statuts déclencheurs** — les statuts de commande qui déclenchent la règle (ou le badge **N'importe quel statut** s'il n'y a pas de restriction).
- **Conditions** — l'ensemble des badges des conditions actives (groupes, pays, devises, catégories, marques, plages). Un tiret signifie qu'il n'y a aucune condition.
- **Actif** — l'interrupteur d'activation de la règle (Oui/Non).
- **Actions** — modification et suppression.

_Capture d'écran : le tableau des règles avec ses colonnes et ses badges._
![img_4.png](img_4.png)

<a id="t7"></a>

### Actions disponibles

**Modifier** — ouvre le formulaire de modification de la règle.

**Supprimer** — supprime la règle (avec confirmation). Les bons déjà émis à partir d'elle restent dans le tableau des bons de réduction.

**Actif (Oui/Non)** — un interrupteur rapide directement dans la ligne : une règle désactivée ne participe pas à l'émission des bons.

**Flèches de priorité (▲ ▼)** — déplacent la règle plus haut ou plus bas dans l'ordre de vérification.

_Capture d'écran : les boutons Modifier / Supprimer et l'interrupteur Actif._
![img_5.png](img_5.png)

<a id="t8"></a>

### Priorité et ordre des règles

Les règles sont vérifiées **de haut en bas**, par priorité. Cet ordre est important lorsque :

- plusieurs règles ont l'option **Arrêter après cette règle** activée — la première règle correspondante selon la priorité s'applique, les autres ne sont pas vérifiées ;
- vous souhaitez qu'une règle plus « spécifique » (par exemple pour un groupe VIP) ait une chance de s'appliquer avant une règle générale.

Les priorités sont automatiquement maintenues en séquence continue de 1 à N (un déplacement par les flèches renumérote l'ensemble).

_Capture d'écran : déplacement d'une règle à l'aide des flèches de priorité._
![img_6.png](img_6.png)

---

<a id="t9"></a>

## 6. Création et modification d'une règle (Rule)

Le formulaire de règle s'ouvre avec le bouton **Ajouter une règle** ou **Modifier**. Il se divise en quatre onglets : **Général**, **Conditions**, **Code**, **E-mail**. En bas se trouvent les boutons **Enregistrer** et **Annuler** (communs à tous les onglets).

<a id="t10"></a>

### 6.1 Onglet Général (principal)

**Nom de la règle** (Rule name) — le nom interne, affiché dans le tableau des règles. Champ obligatoire.

**Nom du bon** (Voucher name) — le nom du bon tel que le client le voit. Vide = la valeur par défaut « Next Order Discount » est utilisée.

**Description du bon** (Voucher description) — une description facultative, enregistrée sur le bon (visible dans le back-office).

**Actif** (Active) — la règle est-elle activée (Oui/Non).

Bloc remise :

- **Type de remise** (Discount type) :
  - **Pourcentage (%)** — un pourcentage du montant de la prochaine commande ;
  - **Montant fixe** — une somme fixe (dans la devise) ;
  - **Livraison gratuite**.
- **Valeur de la remise** (Discount value) — le montant de la remise. Pour un pourcentage, il est plafonné à 100. Le libellé de droite (`%` ou le symbole monétaire) s'adapte automatiquement au type choisi ; avec le type **Livraison gratuite**, le champ est masqué (aucune valeur de remise n'est nécessaire).
- **Durée de validité (jours)** (Validity period) — la durée de validité du bon en jours (nombre entier, au moins 1).
- **Montant minimum de la prochaine commande** (Minimum next order amount) — le montant minimum de la prochaine commande à partir duquel le bon est applicable. `0` = sans restriction.

Bloc logique d'attribution :

- **Arrêter après cette règle** (Stop after this rule) — si Oui, les autres règles ne sont plus vérifiées après le déclenchement de celle-ci (le client reçoit un seul bon). Si Non, les autres règles correspondantes pourront elles aussi émettre leurs bons.

Bloc rappels :

- **Envoyer les rappels** (Send reminders) — activer les e-mails de rappel concernant un bon non utilisé. Les rappels s'arrêtent automatiquement dès que le bon est utilisé ou expiré.
- **Calcul des rappels** (Reminder timing) — à partir de quoi compter les jours :
  - **Jours après l'e-mail du bon** — N jours après l'e-mail du bon ;
  - **Jours avant l'expiration du bon** — N jours avant l'expiration du bon.
- **Premier rappel (jours)** / **Deuxième rappel (jours)** — le calendrier du 1er et du 2e rappel. `0` ou vide = ce rappel n'est pas envoyé.

_Capture d'écran : l'onglet Général du formulaire de règle._
![img_7.png](img_7.png)

<a id="t11"></a>

### 6.2 Onglet Conditions

Toutes les conditions définies doivent être remplies simultanément (logique ET). Une condition vide ou réglée sur `Tous` ne restreint rien.

**Déclencher sur les statuts de commande** (Trigger on order statuses) — les statuts dans lesquels une commande peut donner droit à un bon. La règle est vérifiée à la création de la commande et à chaque changement de son statut. Une liste vide signifie que le statut ne restreint pas la règle. Pour sélectionner plusieurs statuts, maintenez Ctrl/Cmd. Le bon n'est émis que si la commande remplit également les autres conditions de la règle.

Conditions par liste — chacune dispose d'un mode et d'une liste :

- **Groupes de clients** (Customer groups) ;
- **Pays** (Countries) — d'après l'adresse de livraison de la commande ;
- **Devises** (Currencies) — la devise de la commande ;
- **Catégories de produits** (Product categories) — les catégories des produits de la commande ;
- **Marques** (Brands) — les marques (fabricants) des produits de la commande.

Le mode de chacune :

- **Tous (sans restriction)** — ne rien restreindre (la liste est ignorée) ;
- **Uniquement les éléments sélectionnés** — la règle ne s'applique qu'aux éléments sélectionnés ;
- **Tous sauf les éléments sélectionnés** — la règle s'applique à tous, sauf aux éléments sélectionnés.

La liste de sélection n'apparaît qu'avec les modes **Uniquement les éléments sélectionnés** / **Tous sauf les éléments sélectionnés** ; avec le mode **Tous**, elle est masquée pour ne pas encombrer l'écran.

Conditions par plage :

- **Total de la commande d'origine** (Source order total) — la plage de montant de la commande d'origine (**Min** / **Max**). `0` = pas de restriction sur cette borne.
- **Période d'activité** (Active date window) — la fenêtre d'activité de la règle (**Du** / **Au**). Les deux vides = la règle est toujours active.
- **Nombre de commandes du client** (Customer order number) — combien de commandes le client doit avoir (**Min** / **Max**). Les deux valeurs à `1` = uniquement la première commande. `0` = sans restriction. Les commandes d'un même client sont comptées dans la boutique courante par e-mail, même si PrestaShop a créé plusieurs fiches client pour la même adresse ; la commande en cours est déjà incluse dans ce total.
- **Clients enregistrés uniquement** (Registered customers only) — si **Oui**, les commandes en mode invité ne participent pas à la règle. Si **Non**, la règle traite de la même manière les clients enregistrés et les invités. Il est recommandé d'activer cette option pour les scénarios « première commande » et de réactivation client : lors d'une commande en mode invité, PrestaShop peut créer une nouvelle fiche client à chaque fois, si bien qu'un invité de retour ne peut être identifié de façon fiable que par correspondance de l'e-mail.

> Les conditions sur les catégories et les marques portent sur les produits de la **commande d'origine**. Le module ne charge les données produit que lorsqu'au moins une règle active filtre effectivement par catégories ou par marques — cela économise des ressources sur les autres commandes.

_Capture d'écran : l'onglet Conditions._
![img_8.png](img_8.png)

<a id="t12"></a>

### 6.3 Onglet Code (format du code)

Le format du code de bon se définit **pour chaque règle**. Tout champ peut rester vide — la valeur par défaut intégrée est alors utilisée.

- **Longueur de la clé** (Key length) — le nombre de caractères aléatoires dans `%key%` (limité à la plage 4–32).
- **Type de clé** (Key type) — le jeu de caractères utilisé pour la génération :
  - **Alphabétique (A-Z)** — uniquement des lettres ;
  - **Numérique (0-9)** — uniquement des chiffres ;
  - **Alphanumérique (A-Z, 0-9)** — lettres et chiffres.
- **Modèle de clé** (Key template) — le modèle de code avec la variable `%key%`. Exemple : `NOD-%key%` → `NOD-AB12CD8X`.

_Capture d'écran : l'onglet Code._
![img_9.png](img_9.png)

<a id="t13"></a>

### 6.4 Onglet E-mail (messages)

Chaque règle a **ses propres e-mails**, pré-remplis avec le modèle par défaut. Trois types sont configurables :

- **E-mail du bon** (Coupon email) — l'e-mail du bon (envoyé à l'émission du bon) ;
- **E-mail du premier rappel** (First reminder email) — le premier rappel ;
- **E-mail du deuxième rappel** (Second reminder email) — le second rappel.

Pour chaque type :

- un sélecteur de **langue** (par code ISO) — l'objet et le HTML se définissent **séparément pour chaque langue** de la boutique ;
- le champ **Objet** (Subject) — l'objet de l'e-mail ;
- le champ **Contenu HTML** (HTML content) — le corps HTML de l'e-mail ;
- sous le champ HTML, la ligne **Variables disponibles (cliquez pour insérer)** : des « puces » de variables cliquables. Un clic insère la variable directement dans le champ HTML, à la position du curseur — pratique pour ne pas les saisir à la main.

Les variables remplacées par les valeurs réelles lors de l'envoi :

- bon : `{coupon_code}`, `{coupon_value}`, `{valid_to}`, `{minimum_amount}` ;
- client : `{customer_firstname}`, `{customer_lastname}`, `{customer_fullname}`, `{customer_title}` (la civilité, par exemple « Mr »/« Mrs » ; vide si le genre n'est pas renseigné), `{customer_email}` ;
- boutique : `{shop_name}`, `{shop_url}`, `{shop_logo}`. La variable `{shop_url}` est prise en charge lors de l'envoi, mais doit au besoin être saisie manuellement dans le HTML.

Chaque langue utilise son propre objet et son propre HTML — les textes des autres versions linguistiques ne sont pas repris. Si l'objet ou le HTML n'ont pas été enregistrés pour la langue sélectionnée, le module utilise le modèle intégré : le français pour **FR**, l'anglais pour **EN** et toutes les autres langues. Avant d'activer une règle, complétez et enregistrez les e-mails pour toutes les langues de la boutique.

Actions disponibles sous chaque e-mail :

- **Aperçu** (Preview) — prévisualisation de l'e-mail avec des valeurs d'exemple pour les variables (s'ouvre dans une fenêtre).
- **Envoyer un e-mail de test** (Send test email) — envoi d'une copie de test à l'adresse indiquée (également avec des valeurs d'exemple). Pratique pour vérifier la mise en page avant un envoi réel.

_Capture d'écran : l'onglet E-mail._
![img_10.png](img_10.png)

<a id="t14"></a>

### Actions du formulaire

- **Enregistrer** — vérifie et enregistre la règle, puis revient à la liste des règles. En cas d'erreur de validation, le formulaire reste ouvert avec des indications.
- **Annuler** — revient à la liste sans enregistrer.

_Capture d'écran : les boutons Enregistrer / Annuler._
![img_13.png](img_13.png)

---

<a id="t15"></a>

## 7. Onglet Bons de réduction : bons émis

L'onglet **Bons de réduction** est la liste de tous les bons générés (consultation seule + actions manuelles d'envoi).

<a id="t16"></a>

### Filtres

- **Statut** — filtre par statut du bon (created / emailed / reminded / used / expired / canceled) ou « Tous les statuts ».
- **Code** — recherche par code de bon.
- Les boutons **Filtrer** et **Réinitialiser**.

La liste est paginée (30 enregistrements par page).

_Capture d'écran : les filtres de l'onglet Bons de réduction._
![img_11.png](img_11.png)

<a id="t17"></a>

### Colonnes du tableau

- **Code** — le code du bon.
- **Client** — le nom et l'e-mail du client (ou son identifiant si le nom n'est pas disponible).
- **Commande d'origine** — le numéro de la commande à l'origine du bon.
- **Règle** — la règle qui a créé le bon.
- **Statut** — le statut actuel (avec un badge coloré). Des badges `1` / `2` peuvent l'accompagner — ils indiquent quels rappels ont déjà été envoyés.
- **Valable jusqu'au** — la durée de validité du bon ; la date et l'heure sont affichées au format de la locale PrestaShop courante.
- **Créé** — la date et l'heure de création, au format de la locale PrestaShop courante.
- **Actions** — les actions manuelles (voir ci-dessous).

_Capture d'écran : le tableau des bons._
![img_12.png](img_12.png)

<a id="t18"></a>

### Envoi manuel de l'e-mail et des rappels

Tant que le bon est **encore utilisable** (ni used, ni expired, ni canceled), la colonne Actions propose :

- **Langue de l'e-mail à envoyer** — le choix de la langue pour l'envoi manuel (affiché si la boutique compte plus d'une langue). La langue de la commande d'origine est sélectionnée par défaut ; la valeur choisie s'applique aussi bien au renvoi du bon qu'à l'envoi manuel d'un rappel ;
- le bouton **enveloppe** (info-bulle **Renvoyer l'e-mail du bon au client**) — renvoyer l'e-mail du bon ;
- les boutons **cloche avec le numéro 1 / 2** — envoyer immédiatement le rappel correspondant (ils apparaissent si ces rappels sont activés dans la règle du bon).

Pour les bons utilisés, expirés et annulés, ces actions ne sont pas disponibles — leur e-mail et leurs rappels n'ont plus lieu d'être.

_Capture d'écran : les boutons de renvoi et de rappel dans la ligne d'un bon._
![img_13.png](img_13.png)

---

<a id="t19"></a>

## 8. Onglet Paramètres

L'onglet **Paramètres** contient les réglages généraux du module (les remises et les conditions se définissent dans les règles, pas ici).

- **Module actif** (Module active) — l'interrupteur général. Si **Non**, aucun bon n'est émis pour les nouvelles commandes, quelles que soient les règles.
- **Annuler le bon sur ces statuts de commande** (Cancel coupon on order statuses) — les statuts de commande dont le passage **annule** le bon émis par cette commande (il est désactivé et marqué canceled), aucun nouveau bon n'étant créé pour cette commande. Par défaut : « Annulé » et « Remboursé ». Vide = ne jamais annuler automatiquement. Sélection multiple avec Ctrl/Cmd.
- **Mode débogage** (Debug mode) — journalisation détaillée pour le diagnostic. En production, laissez-le désactivé.
- **Conserver les journaux pendant (jours)** (Keep logs for) — la durée de conservation des entrées du journal ; les plus anciennes sont supprimées automatiquement (pendant le cron). `0` = conservation illimitée.

Cliquez sur **Enregistrer** pour appliquer. Les réglages sont enregistrés dans le contexte de la boutique courante (multiboutique).

_Capture d'écran : l'onglet Paramètres._
![img_14.png](img_14.png)

---

<a id="t20"></a>

## 9. Onglet Cron/Outils : tâches de fond

Après la création d'un bon, le module tente immédiatement d'envoyer l'e-mail principal. Si l'envoi échoue, l'e-mail passe en file d'attente et le **cron** réessaie. Le cron planifie et envoie également les e-mails de rappel et fait passer les bons arrivés à échéance au statut **expired**.

<a id="t21"></a>

### Configuration du cron

La méthode recommandée consiste à ajouter **une seule ligne** au crontab du serveur, qui appelle toutes les 5 minutes la tâche combinée par HTTP. Cette méthode fonctionne sur n'importe quel hébergement et ne dépend pas de la version de PHP du serveur.

L'onglet propose :

- **Installation en un clic** (One-click install) — si la configuration automatique du crontab est possible sur le serveur, le bouton **Installer le cron automatiquement** ajoute la ligne nécessaire et **Supprimer le cron** la retire. La ligne est encadrée de marqueurs et est également supprimée lors de la désinstallation du module. Si l'installation automatique n'est pas disponible (par exemple si `shell_exec` est bloqué en hébergement mutualisé), le module en explique la raison et propose de copier la ligne manuellement.
- **Ligne crontab (curl / wget)** — des lignes prêtes à coller manuellement dans le crontab.
- **Ou utilisez un service cron externe** — l'URL destinée aux services web-cron externes (par exemple cron-job.org), avec un intervalle de 5 minutes.
- **Exécuter toutes les tâches maintenant** — lancement manuel de toutes les tâches d'un coup (vérification rapide du bon fonctionnement de l'URL).
- **Votre serveur** — un test de l'environnement : version de PHP, présence de curl (CLI), disponibilité de shell_exec — afin de vous orienter vers la méthode qui fonctionnera.

> **Gardez le jeton secret.** Les URL des tâches contiennent un jeton secret : toute personne qui connaît une URL peut lancer la tâche correspondante.

_Capture d'écran : le bloc de configuration du cron._
![img_15.png](img_15.png)

<a id="t22"></a>

### État des tâches de fond

Le tableau **Tâches** liste les tâches de fond, leur fréquence recommandée, l'heure de leur dernière exécution, leur URL personnelle, l'état du verrou et un bouton d'exécution manuelle.

Les tâches du module :

- **Traiter la file d'envoi** — réessaie l'envoi échoué de l'e-mail principal et envoie les rappels planifiés. Recommandé **toutes les 5 minutes**.
- **Planifier les rappels de bons** — repère les rappels arrivés à échéance et les place en file d'attente. Recommandé **toutes les 30 minutes**.
- **Expirer les bons périmés** — fait passer les bons échus au statut expired. Recommandé **une fois par jour**.

La colonne **Dernière exécution** indique l'état de la tâche : **OK** (elle s'exécute à l'heure), **En retard** (elle prend du retard), **Non actif** (elle ne s'est pas exécutée depuis longtemps), **Jamais exécuté** (aucune exécution). La colonne **Verrou** indique si la tâche est en cours d'exécution (**En cours d'exécution**) ou libre (**Libre**) — le verrou empêche deux exécutions de se chevaucher.

Un bloc distinct, **Cron géré**, indique si la ligne de cron a été installée par le module lui-même.

_Capture d'écran : le tableau des tâches avec leur fréquence et leur état actuel._
![img_16.png](img_16.png)

<a id="t23"></a>

### File d'envoi

En bas se trouve un aperçu de la file : **En attente / En cours / Terminé / Échec**. Une file « En attente » qui gonfle ou un nombre d'échecs notable sont autant de raisons de vérifier le cron et la configuration de messagerie.

_Capture d'écran : l'aperçu de la file d'envoi._
![img_17.png](img_17.png)

---

<a id="t24"></a>

## 10. Onglet Journaux : le journal

L'onglet **Journaux** est le journal des événements du module (émission des bons, envoi des e-mails, erreurs de hooks, etc.).

- Le filtre **Niveau** — le niveau de l'entrée : debug / info / warning / error (ou « Tous »).
- Le filtre **Canal** — le canal (par exemple `cron`, `queue`, `coupon`).
- Les colonnes : **Date**, **Niveau** (avec un badge coloré), **Canal**, **Message** (avec les détails du contexte), **Corrélation** (l'identifiant qui relie entre elles les entrées d'un même événement).

La liste est paginée. Le niveau de détail de la journalisation dépend du **Mode débogage**, et la durée de conservation du réglage **Conserver les journaux pendant** (les deux réglages se trouvent dans l'onglet [Paramètres](#t19)). Dans le contexte d'une boutique précise, les entrées générales de cron et de file créées sans rattachement à une boutique unique sont également affichées : cela permet de voir les erreurs de fond même lorsque le mode débogage est désactivé. L'entrée relative à l'émission d'un bon contient l'`id_lang` et le code ISO de la langue dans laquelle l'e-mail automatique sera envoyé.

_Capture d'écran : l'onglet Journaux avec ses filtres._
![img_18.png](img_18.png)

---

<a id="t25"></a>

## 11. Assistance

En bas des pages du module figure un bloc **Besoin d'aide ?** avec un lien vers le formulaire de contact officiel de PrestaShop Addons :

https://addons.prestashop.com/contact-form.php

Contactez-nous si :

- vous avez besoin d'aide pour la configuration initiale des règles ou du cron ;
- les bons ou les e-mails ne se comportent pas comme prévu ;
- vous souhaitez une évolution ou une personnalisation des fonctionnalités ;
- vous rencontrez des erreurs ou un comportement instable ;
- vous avez des idées d'amélioration.

_Capture d'écran : le bloc d'assistance._
![img_19.png](img_19.png)

---

<a id="t26"></a>

## 12. Démarrage rapide (configuration en 5 à 10 minutes)

1. Ouvrez l'onglet **Paramètres** et réglez **Module actif = Oui**. Configurez au besoin **Annuler le bon sur ces statuts de commande**. Cliquez sur **Enregistrer**.
2. Configurez le **cron** dans l'onglet **Cron/Outils** : cliquez sur **Installer le cron automatiquement** (si c'est possible) ou copiez la ligne recommandée dans le crontab / un service web-cron externe. Cliquez sur **Exécuter toutes les tâches maintenant** pour vérifier.
3. Créez une règle dans l'onglet **Règles → Ajouter une règle** :
   - **Général** : le nom, le type et la valeur de la remise, la durée de validité et, au besoin, le montant minimum de la prochaine commande et les rappels ;
   - **Conditions** : les statuts de commande qui donnent droit au bon et, si nécessaire, des restrictions (groupes, pays, montants, rang de commande, etc.) ; pour les règles de première commande ou de réachat, décidez s'il faut activer **Clients enregistrés uniquement** ;
   - **Code** : le format du code (ou laissez la valeur par défaut) ;
   - **E-mail** : vérifiez les textes des e-mails, utilisez **Aperçu** et **Envoyer un e-mail de test**.
4. Enregistrez la règle et assurez-vous qu'elle est bien **Actif = Oui**.
5. Testez la règle sur une commande de test : faites passer la commande à l'un des statuts indiqués dans la règle et vérifiez qu'un bon apparaît dans l'onglet **Bons de réduction** et que l'e-mail a été envoyé. Si la première tentative a échoué, lancez les tâches de fond manuellement depuis l'onglet **Cron/Outils**.
6. Suivez les résultats dans l'onglet **Tableau de bord** : analysez l'entonnoir, le taux de conversion et l'évolution quotidienne des 30 derniers jours.

---

<a id="t27"></a>

## 13. Check-list de diagnostic

**Le bon n'est pas créé :**

1. **Module actif = Oui** (Paramètres).
2. Il existe au moins une règle avec **Actif = Oui** (Règles).
3. La commande passe réellement à l'un des **Statuts déclencheurs** de la règle (ou la règle accepte « n'importe quel statut »).
4. La commande remplit **toutes** les conditions de la règle : plage de montant, fenêtre de dates, rang de commande du client, type de client (invité ou enregistré), groupes/pays/devises/catégories/marques.
5. Vérifiez l'ordre : si une règle de priorité supérieure a l'option **Arrêter après cette règle**, les règles situées en dessous ne sont pas vérifiées.
6. Consultez les **Journaux** (avec **Mode débogage = Oui**, les entrées sont plus nombreuses).

**Le client ne reçoit pas l'e-mail :**

1. Vérifiez la configuration de messagerie de la boutique ; envoyez un **e-mail de test** depuis l'onglet E-mail de la règle.
2. Pour un bon précis, vous pouvez choisir la langue voulue et cliquer sur le bouton enveloppe dans l'onglet Bons de réduction.
3. Si la première tentative d'envoi a échoué, assurez-vous que le **cron** fonctionne (Cron/Outils → tâche **Traiter la file d'envoi**, statut **OK**) et cliquez sur **Exécuter toutes les tâches maintenant** pour réessayer.
4. La file ne doit pas contenir d'entrées **En attente** bloquées ni un nombre croissant d'**Échec** (Cron/Outils → **File d'envoi**).
5. Si l'e-mail est arrivé dans la mauvaise langue, vérifiez le texte de cette langue dans l'onglet **E-mail** de la règle. En automatique, le module utilise la langue de la commande ; en envoi manuel, la langue sélectionnée à côté des boutons d'action.

**Les rappels ne partent pas :**

1. La règle a **Envoyer les rappels = Oui** et un calendrier défini (**Premier/Deuxième rappel** > 0).
2. La tâche **Planifier les rappels de bons** fonctionne (Cron/Outils).
3. Le bon est **encore utilisable** (ni used, ni expired, ni canceled) — aucun rappel n'est envoyé pour les bons qui ne sont plus valables.

**Le bon a été annulé de façon inattendue :**

- La commande d'origine est passée à un statut figurant dans la liste **Annuler le bon sur ces statuts de commande** (Paramètres). Retirez ce statut de la liste si ce comportement n'est pas souhaité.

**Les bons n'expirent pas (ils restent dans leurs anciens statuts) :**

- La tâche **Expirer les bons périmés** ne fonctionne pas — vérifiez le cron (Cron/Outils).
