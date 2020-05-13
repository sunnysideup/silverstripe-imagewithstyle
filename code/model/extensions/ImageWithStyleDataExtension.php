<?php



class ImageWithStyleDataExtension extends DataExtension
{
    /**
     * adds a field in the CMS to select a list, you pass it the CMS Fields
     *
     * @param FieldList fields
     * @param string    $tabName
     * @param string    $methodName
     * @param string    $folderName
     */
    public function AddSelectImageListField($fields, $tabName, $methodName, $folderName = '')
    {
        if ($this->owner->exists()) {

            //selected one ...
            $fieldName = $methodName.'ID';
            $selectedList = $this->owner->$methodName();
            $defaultList = ImagesWithStyleSelection::create_or_update_default_entry($this->owner, $methodName, $folderName);
            if(! $selectedList) {
                $selectedList = $defaultList;
                $this->owner->$fieldName = $selectedList->ID;
            } else {
                if($selectedList->ID !== $defaultList->ID) {
                    $foldername = ImagesWithStyleSelection::create_folder_name($this->owner, $methodName, $folderName);
                    $selectedList->createFolder($folderName);
                }
            }
            $selectedList->write();

            //show recommend one!
            // show dropdown field
            $list = ImagesWithStyleSelection::get()->map()->toArray();
            if ($defaultList && $defaultList->exists()) {
                $myID = $defaultList->ID;
                $list[$myID] = ' *** '.$list[$myID]." [RECOMMENDED] ";
                asort($list);
            }
            $fields->addFieldsToTab(
                'Root.'.$tabName,
                [
                    HasOneButtonField::create($methodName, 'Edit Existing List', $this->owner),
                    $imageListField = DropdownField::create(
                        $methodName.'ID',
                        'Selected',
                        [0 => '--- Select ---'] + $list
                    )
                ]
            );

            // show links to folder
            if ($imageListField && $selectedList) {
                ImagesWithStyleCMSAPI::add_links_to_folder_field($imageListField, $selectedList);
            }

            // show images associated with current list.
            if ($selectedList->exists()) {
                $config = GridFieldConfig_RecordEditor::create()->removeComponentsByType('GridFieldAddNewButton');
                $fields->addFieldsToTab(
                    'Root.'.$tabName,
                    [
                        GridField::create(
                            $methodName.'_Images',
                            'Included Are',
                            $selectedList->StyledImages(),
                            $config
                        ),
                        ImagesWithStyleCMSAPI::create_new_images_with_style_list_button()
                    ]
                );
            }
        } else {
            $fields->addFieldsToTab(
                'Root.'.$tabName,
                [
                    LiteralField::create(
                        $methodName.'_LINK',
                        '<h2>First save this page and then add images to it.</h2>'
                    )
                ]
            );
        }
    }

}
