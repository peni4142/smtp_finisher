<?php

declare(strict_types=1);

namespace PeerNissen\SmtpFinisher\EventListener;

use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;

class DataStructureIdentifierListener
{

    /**
     * Removes the unnecessary field definitions caused by incorrect parsing in the form framework
     */
    public function modifyDataStructure(AfterFlexFormDataStructureParsedEvent $event): void
    {
        $identifier = $event->getIdentifier();
        if (!isset($identifier['ext-form-persistenceIdentifier'])) {
            return;
        }

        $dataStructure = $event->getDataStructure();
		
	    if(!empty($dataStructure['sheets'])){
		    foreach($dataStructure['sheets'] as $sheetIdentifier => &$sheetConfiguration){
			    if($sheetIdentifier === 'sDEF') continue;
			    if(isset($sheetConfiguration['ROOT']['el']) && is_array($sheetConfiguration['ROOT']['el']) && !empty($sheetConfiguration['ROOT']['el'])){
				    foreach ($sheetConfiguration['ROOT']['el'] as $fieldKey => $field){
					    // Group all elements by their keys
					    $matchingElements = array_filter(
						    $sheetConfiguration['ROOT']['el'],
						    function ($key) use ($fieldKey){
							    return(str_contains($key, $fieldKey));
						    },
						    ARRAY_FILTER_USE_KEY
					    );
					
					    if(count($matchingElements) > 1){ // There are redundant elements in here
						    foreach ($matchingElements as $elementKey => $element){
							    if(isset($element['type']) && $element['type'] === 'array' && $element['section'] === TRUE){ // This is the main element, it is an array displayed as a section
								    // Add the label to the title, as labels are not rendered in sections
								    // This is not strictly necessary, just personal taste
								    $sheetConfiguration['ROOT']['el'][$elementKey]['title'] .= ' ' . $sheetConfiguration['ROOT']['el'][$elementKey]['label'];
							    } else { // Redundant element definition, remove it
								    unset($sheetConfiguration['ROOT']['el'][$elementKey]);
							    }
						    }
					    }
				    }
			    }
		    }
	    }

        $event->setDataStructure($dataStructure);
    }
}
