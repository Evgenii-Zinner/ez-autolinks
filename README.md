# Autolinks Plugin

The **EZ Autolinks** Plugin is an extension for [Grav CMS](https://github.com/getgrav/grav). It automatically inserts links in any page of your website for particular words

## Installation

Installing the Autolinks plugin can be done in one of three ways: The GPM (Grav Package Manager) installation method lets you quickly install the plugin with a simple terminal command, the manual method lets you do so via a zip file, and the admin method lets you do so via the Admin Plugin.

### GPM Installation (Preferred)

To install the plugin via the [GPM](https://learn.getgrav.org/cli-console/grav-cli-gpm), through your system's terminal (also called the command line), navigate to the root of your Grav-installation, and enter:

    bin/gpm install ez-autolinks

This will install the Autolinks plugin into your `/user/plugins`-directory within Grav. Its files can be found under `/your/site/grav/user/plugins/ez-autolinks`.

### Manual Installation

To install the plugin manually, download the zip-version of this repository and unzip it under `/your/site/grav/user/plugins`. Then rename the folder to `ez-autolinks`. You can find these files on [GitHub](https://github.com//grav-plugin-ez-autolinks) or via [GetGrav.org](https://getgrav.org/downloads/plugins).

You should now have all the plugin files under

    /your/site/grav/user/plugins/ez-autolinks
	
> NOTE: This plugin is a modular component for Grav which may require other plugins to operate, please see its [blueprints.yaml-file on GitHub](https://github.com//grav-plugin-ez-autolinks/blob/main/blueprints.yaml).

### Admin Plugin

If you use the Admin Plugin, you can install the plugin directly by browsing the `Plugins`-menu and clicking on the `Add` button.

## Configuration

Before configuring this plugin, you should copy the `user/plugins/autolinks/ez-autolinks.yaml` to `user/config/plugins/ez-autolinks.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
//Turning plugin on or off
enabled: true
//List of words, separated by comma and related to them links
links:
  //Name of group
  Grav:
    //Words separated by comma
    words: 'Grav, grav'
    //Link
    url: 'https://getgrav.org/'
```

Note that if you use the Admin Plugin, a file with your configuration named ez-autolinks.yaml will be saved in the `user/config/plugins/`-folder once the configuration is saved in the Admin.

## Usage

Enable plugin, add needed words and related links in admin panel (or directly in the configuration file `user/config/plugins/ez-autolinks.yaml`)