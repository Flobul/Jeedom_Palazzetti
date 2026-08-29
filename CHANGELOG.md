# Historique des versions

## 2.0.0

### Nouveautés

- Ajout d'un historique de chauffe sur 1 à 31 jours : chronogramme des cycles, températures, consigne, puissance, ventilation et comparaison quotidienne de la durée et des pellets consommés.
- Accès direct à l'historique du bon équipement depuis les widgets dashboard et mobile.
- Ajout d'une infobulle sur les cartes d'équipement avec les principales informations du poêle et de sa connexion.
- Refonte de la découverte manuelle avec aperçu des appareils et choix entre mise à jour, remplacement de l'équipement ou annulation.
- La découverte automatique actualise uniquement les équipements connus afin d'éviter la création de doublons.

### Fiabilité et sécurité

- Rétablissement de la collecte périodique complète (`GET TIME`, `GET STDT`, `GET CHRD`, `GET CNTR` et `GET ALLS`) et maintien du secours `EXT ADRD`.
- Page Santé enrichie et distinction entre une passerelle hors ligne et un poêle indisponible derrière une passerelle joignable.
- Redécouverte automatique temporisée après plusieurs échecs réseau, notamment en cas de changement d'adresse DHCP.
- Validation renforcée des commandes, des réponses, des équipements AJAX et des adresses réseau locales.
- Écritures PARM/HPAR désactivées par défaut, limitées à `0…255` et journalisées lorsqu'elles sont activées.
- Passage des widgets en JavaScript natif, sécurisation des valeurs injectées et suppression des rafraîchissements complets inutiles.
- Corrections des durées, des compteurs de surchauffes, des métadonnées et de l'affichage de la page Santé.

### Migration

- Activation de l'historisation sans lissage des commandes nécessaires au calcul des cycles et des consommations quotidiennes.
- Découverte automatique désactivée par défaut après migration ; elle doit être réactivée explicitement si elle est souhaitée.
- Ajout de tests automatisés, d'un workflow de qualité et du PHPDoc sur le code PHP.
