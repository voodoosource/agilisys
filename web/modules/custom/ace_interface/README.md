# Agilisys interview task

## Installation
If using Lando, you can run `lando start` and then `lando drush si --db-url=mysql://drupal8:drupal8@database/drupal8 -y` 
If not, install Drupal as you usually would. Copy the ace_interface module into the modules directory.

## Background
The ace_interface module is responsible for 
connecting to Dynamics 365, a Microsoft CRM solution. We use Dynamics 365 as our main data storage for our form-heavy Drupal sites. The ace_interface module is part of our shared ACE (Agilisys Content Engine) module suite, which is used across several projects.

Each website will generally only need one set of Dynamics credentials, but recently we've needed to connect to more than one instance of Dynamics for bigger projects. We store these credentials in a config variable called ace_interface.settings

## Task
AceGuzzleClient has been gradually added to over time through different projects, and needs a bit of a cleanup. 
AceGuzzleClient is used as a wrapper around Guzzle, and handles errors, logging and tracing for all connections to Dynamics. 

Could you refactor this class as you see fit, keeping in mind:
- Coding standards
- Developer experience
- Reusability
- Security
- Resilience
- Testing

The other classes and files in the module are supplied as context for the task, and there is no need to edit these 
unless you feel it is needed.

## Completion
- Complete as much of the refactor as you can within 2 hours. Don't worry if it's not completely finished within the time, we're looking for an idea of how you work!
- Zip up your work, write up an explanation of the changes you've made and why, and send to annika.clarke@agilisys.co.uk for review.
