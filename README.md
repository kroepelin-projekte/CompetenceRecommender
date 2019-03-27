## Installation

### Install CompetenceRecommender-Plugin
In order to install the plugin from github, please follow the commands below:

Start at your ILIAS root directory. 

Run the follow commands:
```bash
mkdir -p Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/
cd Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/
git clone https://github.com/feldbusl/CompetenceRecommender.git
```

Update and activate the plugin in the ILIAS Plugin Administration

Look after `TODO`'s in the plugin code. Maybe you can remove some files (For example config) depending on your use. Also override this inital Readme

### Dependencies (Already exists in `vendor`)
* ILIAS 5.3
* PHP >=7.0
* [composer](https://getcomposer.org)
* [srag/activerecordconfig](https://packagist.org/packages/srag/activerecordconfig)
* [srag/custominputguis](https://packagist.org/packages/srag/custominputguis)
* [srag/dic](https://packagist.org/packages/srag/dic)
* [srag/librariesnamespacechanger](https://packagist.org/packages/srag/librariesnamespacechanger)
* [srag/removeplugindataconfirm](https://packagist.org/packages/srag/removeplugindataconfirm)

Please use it for further development!
