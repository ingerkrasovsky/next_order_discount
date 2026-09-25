# Fiche produit du module Next Order Discount pour PrestaShop

Textes français prêts à l'emploi pour la fiche produit, le site du module et les supports commerciaux. Les formulations décrivent les fonctionnalités réelles de la version 1.0.0, sans promesse de croissance garantie des ventes.

---

## 1. Nom du module

### Variante recommandée

**Next Order Discount : bon personnel pour la prochaine commande**

Le nom explique immédiatement le fonctionnement du module, souligne le caractère personnel de l'offre et contient la requête principale « bon pour la prochaine commande ».

### Variante axée sur l'objectif commercial

**Bon personnel pour la prochaine commande — faites revenir vos clients**

### Variante axée sur l'automatisation

**Bon personnel automatique après l'achat**

---

## 2. Description courte

**Créez une raison de racheter dès la commande terminée : générez automatiquement des bons personnels, envoyez les e-mails et les rappels, et suivez le résultat dans un entonnoir unique.**

---

## 3. Description complète

# Faites revenir votre client avec un bon personnel

Une fois la commande terminée, envoyez à votre client un bon personnel pour son prochain achat — remise en pourcentage, montant fixe ou livraison gratuite.

**Next Order Discount** crée un bon personnel dès qu'une commande remplit les conditions définies, puis envoie au client un e-mail contenant le code et ses conditions d'utilisation. Si le bon reste inutilisé, le module peut envoyer jusqu'à deux rappels automatiques. Les règles d'attribution, les e-mails et les statistiques sont accessibles depuis le back-office de PrestaShop.

## Comment ça fonctionne

1. Vous créez une règle et choisissez les commandes qui participent à la campagne.
2. Lorsqu'une commande remplit toutes les conditions et atteint le statut voulu, le module crée un bon personnel et envoie immédiatement l'e-mail au client.
3. Le module envoie automatiquement les rappels planifiés, relance les envois échoués et met à jour les statuts des bons.
4. Le tableau de bord vous montre le parcours des bons, de leur création à leur utilisation.

Une fois la configuration initiale terminée, la campagne fonctionne toute seule et les résultats restent sous votre contrôle.

## Créez des offres différentes pour des objectifs différents

Plutôt qu'une remise unique pour tous, créez des offres distinctes pour les nouveaux clients, les clients fidèles, les commandes importantes, certains pays ou certaines catégories de produits. Chaque règle définit qui reçoit un bon, quel avantage il obtient et pendant combien de temps il peut en profiter.

Chaque règle propose trois types d'avantage :

- une remise en pourcentage ;
- un montant de remise fixe ;
- la livraison gratuite.

La durée de validité du bon et le montant minimum de la prochaine commande se règlent séparément.

Exemples de scénarios :

- **10 % après la première commande** — inciter en douceur un nouveau client à passer au deuxième achat ;
- **15 € de remise après une commande de 120 € ou plus** — récompenser les clients au panier élevé ;
- **livraison gratuite pour les clients fidèles** — offrir un privilège à un groupe précis ;
- **bon saisonnier sur certaines marques ou catégories** — soutenir une campagne ciblée sans solder tout le catalogue.

## N'attribuez un bon qu'aux bons clients

Toutes les conditions d'une règle sont vérifiées simultanément. Vous pouvez paramétrer :

- les statuts de commande qui déclenchent la création du bon ;
- le montant minimum et maximum de la commande d'origine ;
- la période d'activité de la campagne ;
- le rang de la commande du client — par exemple uniquement la première commande, ou à partir de la troisième ;
- les groupes de clients ;
- les pays ;
- les devises ;
- les catégories de produits ;
- les marques.

Pour les conditions par liste, trois modes sont disponibles : « Tous », « Uniquement les éléments sélectionnés » et « Tous sauf les éléments sélectionnés ».

Les règles sont évaluées par priorité. L'option d'arrêt de l'évaluation vous permet de décider si le client reçoit un seul bon ou plusieurs bons issus de différentes règles correspondantes.

## Adaptez chaque e-mail à votre campagne

Chaque règle dispose de son propre jeu d'e-mails :

- l'e-mail principal contenant le bon ;
- le premier rappel ;
- le second rappel.

L'objet et le contenu HTML se configurent séparément pour chaque langue de la boutique. Les variables insèrent le code et le montant de la remise, la date d'expiration, le montant minimum de commande, le nom du client, le nom de la boutique et d'autres données.

Avant de lancer la campagne, ouvrez l'aperçu de l'e-mail et envoyez-vous une copie de test.

## Ramenez l'attention sur un bon inutilisé

Configurez un ou deux rappels et choisissez le mode de calcul de la date :

- un nombre de jours défini après l'envoi de l'e-mail principal ;
- un nombre de jours défini avant l'expiration du bon.

Si le bon a déjà été utilisé, a expiré ou a été annulé, plus aucun rappel n'est envoyé.

## Maîtrisez le format du code promo

Pour chaque règle, vous pouvez configurer :

- la longueur de la partie aléatoire du code ;
- le jeu de caractères — lettres, chiffres ou les deux ;
- un modèle avec la variable `%key%`.

Par exemple, le modèle `RETURN-%key%` peut produire le code `RETURN-AB12CD8X`.

## Suivez vos résultats dans un entonnoir unique

Le tableau de bord affiche le nombre de bons à chaque étape :

**créés → envoyés → relancés → utilisés → expirés → annulés**.

Il calcule également le taux de conversion des bons créés en bons utilisés et affiche l'état de la file des renvois et des rappels. Vous évaluez ainsi l'utilisation réelle de l'offre et repérez à temps un problème d'envoi.

## Automatisez les tâches de fond

Le planificateur cron prend en charge la planification et l'envoi des rappels, les nouvelles tentatives après l'échec de l'e-mail principal et le passage des bons périmés au statut correspondant.

Dans l'onglet des outils, vous pouvez :

- installer la tâche cron automatiquement si le serveur le permet ;
- copier une commande prête à l'emploi pour un planificateur système ou externe ;
- lancer toutes les tâches manuellement pour les vérifier ;
- consulter l'heure de la dernière exécution et l'état de chaque tâche ;
- contrôler la file des renvois et des rappels.

## Protégez votre campagne des erreurs et des remises superflues

- Pour une commande d'origine et une règle données, le bon n'est créé qu'une seule fois.
- En cas d'annulation ou de remboursement de la commande d'origine, le bon associé peut être désactivé automatiquement.
- L'unicité des codes aléatoires est vérifiée.
- Le journal des événements aide à localiser les erreurs et à suivre les opérations de fond.
- La durée de conservation du journal est configurable.

## Ce que la boutique y gagne

- un scénario de réactivation client prêt à l'emploi dès la commande terminée ;
- des offres personnelles au lieu d'une remise unique pour tous ;
- des bons personnels basés sur les règles panier standard de PrestaShop ;
- des e-mails configurables et jusqu'à deux rappels ;
- la maîtrise de la durée de validité et l'annulation automatique des bons obsolètes ;
- un entonnoir d'utilisation des bons et un journal d'activité du module ;
- la prise en charge du multiboutique et des e-mails dans les langues de la boutique.

---

## 4. Fonctionnalités principales

### Règles et remises

- nombre de règles illimité ;
- priorité des règles et arrêt de l'évaluation ;
- remise en pourcentage, montant fixe ou livraison gratuite ;
- durée de validité et montant minimum de la prochaine commande pour chaque règle ;
- bon personnel rattaché au client ;
- protection contre une double attribution pour une même commande et une même règle ;
- format du code de bon configurable.

### Conditions d'attribution

- statuts de commande sélectionnés ;
- plage de montant de la commande d'origine ;
- période d'activité de la règle ;
- rang de la commande du client ;
- groupes de clients, pays et devises ;
- catégories de produits et marques ;
- modes d'inclusion et d'exclusion pour les conditions par liste.

### E-mails et rappels

- e-mail principal propre à chaque règle ;
- jusqu'à deux e-mails de rappel ;
- textes des e-mails pour chaque langue de la boutique ;
- variables dynamiques ;
- aperçu de l'e-mail ;
- envoi de test ;
- arrêt automatique des rappels après utilisation, annulation ou expiration du bon.

### Contrôle et automatisation

- entonnoir des statuts de bons et taux de conversion ;
- liste des bons émis avec filtres ;
- renvoi manuel de l'e-mail et des rappels ;
- file des renvois et des rappels ;
- assistant de configuration du cron ;
- exécution manuelle des tâches et suivi de leur état ;
- expiration automatique des bons ;
- annulation automatique du bon selon les statuts de la commande d'origine ;
- journal des événements avec filtres et durée de conservation configurable ;
- mode débogage ;
- prise en charge du multiboutique.

---

## 5. Questions fréquentes

### En quoi ce module diffère-t-il d'un code promo classique ?

Un code promo classique est généralement diffusé à tous les clients avant ou pendant l'achat en cours. Next Order Discount crée un bon personnel pour un client précis après une commande éligible et l'incite à revenir pour son prochain achat.

### Quand le bon est-il créé ?

Lorsque la commande atteint l'un des statuts sélectionnés dans la règle et remplit en même temps toutes les autres conditions de cette règle.

### Peut-on n'attribuer le bon qu'après la première commande ?

Oui. Indiquez 1 comme rang minimum et maximum de la commande. Vous pouvez configurer de la même manière une campagne pour la deuxième, la troisième ou les commandes suivantes.

### Peut-on proposer des remises différentes à des clients différents ?

Oui. Créez plusieurs règles avec des conditions, des remises et des priorités distinctes. Vous pouvez tenir compte du groupe client, du pays, de la devise, du montant de la commande, des produits de certaines catégories ou marques, et d'autres paramètres.

### Peut-on émettre plusieurs bons pour une seule commande ?

Oui, si la commande correspond à plusieurs règles et que l'arrêt de l'évaluation n'est pas activé dans ces règles. Si vous ne voulez qu'un seul bon, définissez les priorités et activez l'arrêt sur la règle voulue.

### Quels types de remise sont pris en charge ?

La remise en pourcentage, le montant fixe et la livraison gratuite.

### Peut-on imposer un montant minimum à la prochaine commande ?

Oui. Le montant minimum d'utilisation se définit séparément pour chaque règle.

### Le module envoie-t-il lui-même l'e-mail contenant le bon ?

Oui. Dès le changement de statut d'une commande éligible, le module crée le bon et envoie immédiatement l'e-mail via le système de messagerie de PrestaShop. En cas d'échec de l'envoi, l'e-mail est placé en file d'attente pour une nouvelle tentative via le cron.

### Combien de rappels peut-on envoyer ?

Jusqu'à deux. Chaque rappel peut être désactivé, et sa date d'envoi peut être calculée à partir de l'e-mail principal ou de la date d'expiration du bon.

### Un rappel sera-t-il envoyé après l'utilisation du bon ?

Non. Aucun rappel n'est planifié pour les bons utilisés, expirés ou annulés.

### Que se passe-t-il en cas d'annulation ou de remboursement de la commande d'origine ?

Si le nouveau statut de la commande figure dans la liste des statuts d'annulation, le module désactive le bon associé et le marque comme annulé.

### Peut-on modifier le texte de l'e-mail ?

Oui. Pour chaque règle et chaque langue de la boutique, vous pouvez modifier l'objet et le contenu HTML de l'e-mail principal et des deux rappels. L'aperçu et l'envoi de test sont disponibles.

### Le client verra-t-il un nouveau bloc sur la boutique ?

Non. Le module intervient après la commande et informe du bon par e-mail : aucune intégration de widget dans le thème n'est nécessaire.

### Comment mesurer l'efficacité de la campagne ?

Le tableau de bord indique combien de bons ont été créés, envoyés, utilisés, expirés et annulés, ainsi que le taux de conversion des bons créés en bons utilisés.

### Le multiboutique est-il pris en charge ?

Oui. Les données et les réglages tiennent compte de la boutique sélectionnée, et le mode « toutes les boutiques » donne accès à des statistiques consolidées.

### Quelles versions sont prises en charge ?

Le module est conçu pour PrestaShop 8.1 et versions ultérieures. La version minimale de PHP est 7.2 ; le serveur doit par ailleurs respecter la configuration requise par la version de PrestaShop installée.

---

## 6. Mots-clés de recherche

bon prochaine commande, remise prochaine commande, code promo après achat, bon automatique, bon personnalisé, réachat, ventes récurrentes, retour client, fidélisation client, remise après commande, e-mail avec bon de réduction, rappel de bon, automatisation des remises, règles de remise PrestaShop, bon de réduction PrestaShop, programme de fidélité PrestaShop, livraison gratuite prochaine commande, conversion des bons

---

## 7. Texte pour la rubrique « Nouveautés »

**Version 1.0.0 — première publication**

- Création automatique d'un bon personnel dès le passage de la commande au statut choisi.
- Nombre illimité de règles, avec priorité et conditions flexibles.
- Remise en pourcentage, montant fixe ou livraison gratuite.
- Ciblage par montant et rang de commande, période de campagne, groupes, pays, devises, catégories et marques.
- E-mails propres à chaque règle et à chaque langue de la boutique.
- Jusqu'à deux rappels automatiques.
- Format du code de bon configurable.
- Annulation automatique et gestion de l'expiration.
- Entonnoir des bons, file des renvois et des rappels, outils cron et journal des événements.
- Prise en charge du multiboutique.

---

## 8. Accents recommandés pour la fiche produit

Le premier écran de la fiche doit répondre à trois questions :

1. **Que fait le module ?** Il crée un bon personnel après la commande.
2. **À quoi sert-il ?** Il donne au client une raison concrète de revenir.
3. **Pourquoi la solution est-elle pratique ?** Règles, e-mails, rappels et suivi sont réunis dans un seul module.

Titre recommandé pour le premier écran :

> **Transformez une commande terminée en raison d'acheter à nouveau**

Sous-titre recommandé :

> **Créez des bons personnels après l'achat, rappelez l'offre à vos clients et suivez le résultat dans un entonnoir unique.**

Pour les captures d'écran du listing, il vaut mieux montrer :

1. le tableau de bord avec l'entonnoir des bons ;
2. la liste des règles et leurs priorités ;
3. les conditions de ciblage ;
4. le paramétrage de la remise et de la durée de validité ;
5. l'éditeur et l'aperçu de l'e-mail ;
6. la liste des bons émis ;
7. l'état du cron et de la file des renvois et des rappels.

Sur chaque image, n'utilisez qu'un seul argument court plutôt qu'une liste exhaustive de fonctionnalités. Le fil conducteur de toute la fiche : **« Une raison personnelle de revenir — automatiquement, après chaque commande éligible »**.
