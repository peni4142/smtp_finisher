<?php
defined('TYPO3') or die('Access denied.');
/***************
 * Add default RTE configuration
 */
$GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['smtp_finisher'] = 'EXT:smtp_finisher/Configuration/RTE/Default.yaml';

/***************
 * PageTS
 */
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:smtp_finisher/Configuration/TsConfig/Page/All.tsconfig">');




// Register custom EXT:form configuration
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('form')) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(trim('
        module.tx_form {
            settings {
                yamlConfigurations {
                    281 = EXT:smtp_finisher/Configuration/service.yaml
                    311 = EXT:smtp_finisher/Configuration/Finishers/Smtp.yaml
                }
            }
        }
        plugin.tx_form {
            settings {
                yamlConfigurations {
                    281 = EXT:smtp_finisher/Configuration/service.yaml
                    311 = EXT:smtp_finisher/Configuration/Finishers/Smtp.yaml
                }
            }
        }
    '));
}
