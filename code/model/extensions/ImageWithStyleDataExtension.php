<?php



class ImageWithStyleDataExtension extends DataExtension
{
    public function AddSelectImageList($fields, $tabName, $methodName, $folderName = '')
    {
        if ($this->owner->exists()) {
            $this->owner->createImageWithStyleListAndFolder($methodName, $folderName);
            $obj = $this->owner->$methodName();
            if ($obj && $obj->exists()) {
                $link = $obj->CMSEditLink();
                $title = 'edit '.$obj->Title;
            } else {
                if (! $obj) {
                    $obj = Injector::inst()->get('ImagesWithStyleSelection');
                }
                $link = $obj->CMSAddLink();
                $title = 'add '.$obj->singular_name();
            }
            $standardListName = $this->owner->folderToListName($folderName);

            $list = ImagesWithStyleSelection::get()->map()->toArray();
            $myList = ImagesWithStyleSelection::get()->filter(['Title' => $standardListName]);
            $myListObject = null;
            if ($myList->count() === 1) {
                $myListObject = $myList->first();
                if ($myListObject && $myListObject->exists()) {
                    $myID = $myListObject->ID;
                    $list[$myID] = ' *** '.$list[$myID]." [RECOMMENDED] ";
                    asort($list);
                }
            }
            $fields->addFieldsToTab(
                'Root.'.$tabName,
                [
                    HasOneButtonField::create($methodName, $this->owner->$methodName(), $this->owner),
                    LiteralField::create($methodName.'_OR', '<h2>OR</h2>'),
                    $imageListField = DropdownField::create(
                        $methodName.'ID',
                        'Select Existing Images List',
                        [0 => '--- Select ---'] + $list
                    )
                ]
            );
            if ($imageListField && $myListObject) {
                ImagesWithStyleCMSAPI::add_links_to_folder_field($imageListField, $myListObject);
            }
            $fieldID = $tabName.'ImageSelectionID';
            if ($this->owner->$fieldID) {
                $imageList = ImagesWithStyleSelection::get()->byID($this->owner->$fieldID);
                if ($imageList) {
                    ImagesWithStyleCMSAPI::add_links_to_folder_field($imageListField, $imageList);
                }
            }
            if ($obj->exists() && $obj->StyledImages()->count()) {
                $config = GridFieldConfig_RecordEditor::create()->removeComponentsByType('GridFieldAddNewButton');
                $fields->addFieldsToTab(
                    'Root.'.$tabName,
                    [
                        GridField::create(
                            $methodName.'_Images',
                            'Included Are',
                            $obj->StyledImages(),
                            $config
                        )
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

    public function createImageWithStyleListAndFolder($methodName, $folderName = '')
    {
        //we add plus ten because they are not always identical
        if (strtotime($this->owner->LastEdited) > (strtotime($this->owner->Created) + 5)) {
            if ($folderName === '') {
                $folderName = $methodName.'-for-'.$this->owner->ClassName.'-'.$this->owner->ID;
            }
            $listName = $this->owner->folderToListName($folderName);

            $array = [
                'Title' => $listName
            ];
            $obj = ImagesWithStyleSelection::get()->filter($array)->first();
            if (! $obj) {
                $obj = ImagesWithStyleSelection::create($array);
            }
            $obj->write();
            if ($folderName) {
                $obj->createFolder($folderName);
            }
        }
    }

    public function folderToListName($folderName)
    {
        return str_replace('/', '-', $folderName);
    }
}
