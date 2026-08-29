# Plugin Palazzetti pour Jeedom

[![License](https://badgen.net/github/license/Flobul/Jeedom_Palazzetti?icon=github)](https://github.com/Flobul/Jeedom_Palazzetti)
[![Language](https://badgen.net/static/Language/PHP/blue?icon=github)](https://github.com/Flobul/Jeedom_Palazzetti)
[![Last commit](https://badgen.net/github/last-commit/Flobul/Jeedom_Palazzetti?icon=github)](https://github.com/Flobul/Jeedom_Palazzetti/commits)
[![Open issues](https://badgen.net/github/open-issues/Flobul/Jeedom_Palazzetti?icon=github)](https://github.com/Flobul/Jeedom_Palazzetti/issues)
[![Open pull requests](https://badgen.net/github/open-prs/Flobul/Jeedom_Palazzetti?icon=github)](https://github.com/Flobul/Jeedom_Palazzetti/pulls)

Plugin Jeedom pour superviser et piloter les poêles Palazzetti.

## Version 2

- Les cartes d'équipement donnent au survol un aperçu de la connexion, de l'état du poêle, des températures, de la puissance et de la ventilation.
- La modale **Historique de chauffe** affiche jusqu'à 31 jours de données Jeedom : chronogramme des cycles, température ambiante et consigne, aires de puissance et de ventilation limitées aux périodes allumées, puis comparaison quotidienne de la durée et des pellets consommés.
- Le plugin ne crée aucune base de mesures supplémentaire. Il utilise les historiques des commandes `IStatus`, `ITemp`, `IConsigne`, `IPower`, `IFan`, `ITemp2`, `ITemp3`, `IQuantite` et `IHeuresChauffe`, avec un plafonnement des points envoyés au navigateur.

Lors du passage en `2.0.0`, l'historisation de l'état `IStatus` est activée sans lissage afin de conserver les transitions exactes qui délimitent les cycles. Les compteurs `IQuantite` et `IHeuresChauffe` sont également conservés sans lissage pour calculer des écarts journaliers fiables. Les périodes horaires complètes ne sont donc disponibles qu'à partir de cette mise à jour, tandis que les barres quotidiennes peuvent réutiliser les historiques antérieurs des compteurs.

## Fiabilité et sécurité

- Chaque rafraîchissement configuré relit `GET TIME`, `GET STDT`, `GET CHRD`, `GET CNTR` et `GET ALLS`. Si `GET TIME` échoue, le cycle s'arrête immédiatement pour éviter une série de timeouts.
- La santé distingue une passerelle réseau hors ligne d'un poêle indisponible derrière une passerelle joignable.
- La découverte automatique actualise uniquement les équipements reconnus et ignore les appareils inconnus : elle ne crée donc aucun doublon. La découverte manuelle affiche les informations détectées et propose la mise à jour en place ou la création d'un remplacement avec désactivation de l'ancien.
- Les commandes et adresses sont validées côté serveur. Les écritures PARM/HPAR sont désactivées par défaut, limitées à `0…255` faute de schéma fiable par modèle, et journalisées lorsqu'elles sont activées en mode expert.
