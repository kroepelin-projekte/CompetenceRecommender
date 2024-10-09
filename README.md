# Competence Recommender

![GitHub release (latest SemVer)](https://img.shields.io/github/v/release/fluxapps/competencerecommender?style=flat-square)
![GitHub closed issues](https://img.shields.io/github/issues-closed/fluxapps/competencerecommender?style=flat-square&color=success)
[![GitHub issues](https://img.shields.io/github/issues/fluxapps/competencerecommender?style=flat-square&color=yellow)](https://github.com/fluxapps/competencerecommender/issues)
![GitHub closed pull requests](https://img.shields.io/github/issues-pr-closed/fluxapps/competencerecommender?style=flat-square&color=success)
![GitHub pull requests](https://img.shields.io/github/issues-pr/fluxapps/competencerecommender?style=flat-square&color=yellow)
[![GitHub forks](https://img.shields.io/github/forks/fluxapps/competencerecommender?style=flat-square&color=blueviolet)](https://github.com/fluxapps/competencerecommender/network)
[![GitHub stars](https://img.shields.io/github/stars/fluxapps/competencerecommender?style=flat-square&color=blueviolet)](https://github.com/fluxapps/competencerecommender/stargazers)
[![GitHub license](https://img.shields.io/github/license/fluxapps/competencerecommender?style=flat-square)](https://github.com/fluxapps/Vcompetencerecommender/blob/main/LICENSE.md)

## Installation

### Install CompetenceRecommender-Plugin
In order to install the plugin from github, please follow the commands below:

Start at your ILIAS root directory. 

Run the following commands:
```bash
mkdir -p Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/
cd Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/
git clone https://github.com/fluxapps/CompetenceRecommender.git
```

Update and activate the plugin in the ILIAS Plugin Administration

### Dependencies (Already exists in `vendor`)
* ILIAS 8/9
* compRoles patch of Leifos
* PHP >=7.4
* [composer](https://getcomposer.org)
* PHPUnit ^7

## Usage
The plugin recommends resources to the learners for their progress, depending on which profile they have and their skill-level.

### Requirements
* the users have at least one profile that is activated in the Plugin Configuration
* in the profiles there is at least one competence with assigned material for at least the highest level
* most of the assigned material triggers new formationdata (measurements by tests, selfevaluations by surveys)

### Configuration
* the profiles can be set active or inactive
* for each profile, there is the possibility to set an initiation object (e.g. a boarding test) which is the first recommended object the users will see
* a dropout time for the formationdata can be set (e.g. if at one time no peer evaluation is possible anymore)

### Students' view
* on the Personal Desktop there is a widget where up to three materials are recommended
* by click on the button in the widget, the students see up to five competences with progressbars and, collapsed, the material
* in the tabs, the students can switch to a page where all competences of all their profiles are listed, with possibilities to changed the view mode, filter and sort
* in the tabs, the students can switch to an information page

## Development information
The plugin was developed by Leonie Feldbusch at the University of Freiburg in her thesis 2019 and is 
now maintained by fluxlabs ag. 

## Maintenance
fluxlabs ag, support@fluxlabs.ch
