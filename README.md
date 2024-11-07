# Submissions Citations Report
This plugin generates a CSV report containing all submissions that have at least one citation listed in the following services:

- CrossRef
- Europe PMC
- OpenAlex

For each submission, the following submission metadata is displayed in the report:

- Id
- Title
- List of authors
- Access URL (for dashboard)
- DOI
- SciELO Journal (if the submitter user has the SciELO Journal role)

This plugin was developed with intentions to be used in the SciELO Preprints server. Therefore, the SciELO Journal role is present only at this server.

## Compatibility

The latest release of this plugin is compatible with the following PKP applications:

* OPS 3.4.0

Using PHP 8.1 or later.

## Plugin Download

To download the plugin, go to the [Releases page](https://github.com/lepidus/submissionsCitationsReport/releases) and download the tar.gz package of the latest release compatible with your website.

## Installation

1. Install the plugin dependencies.
2. Enter the administration area of ​​your application and navigate to `Settings`>` Website`> `Plugins`> `Upload a new plugin`.
3. Under __Upload file__ select the file __submissionsCitationsReport.tar.gz__.
4. Click __Save__ and the plugin will be installed on your website.

## Instructions for use
Since the retrieving of submissions citations can be very network demanding, we implemented a scheduled task, which does this job on background and stores a list of submissions with citations on cache.

For the scheduled task to be executed, you need to access the installed plugins page, find the Acron Plugin and use its option "Reload Scheduled Tasks". In this first moment,the report scheduled task will be executed, so you should wait some minutes to generate the report. This time depends greatly on the number of submissions in your server.

After this, you can to `Statistics` > `Reports` and choose `Submissions Citations Report`. If the cache has been generated successfully by the scheduled task, the report shouldn't take more than a few seconds to download.

From then on, the cache will be updated automatically once a month.

## License

__This plugin is licensed under the GNU General Public License v3.0__

__Copyright (c) 2024 Lepidus Tecnologia__

__Copyright (c) 2024 SciELO__