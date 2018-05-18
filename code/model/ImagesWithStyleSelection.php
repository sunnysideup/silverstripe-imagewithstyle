<?php



class ImagesWithStyleSelection extends DataObject
{


    #######################
    ### Names Section
    #######################

    private static $singular_name = 'Selection of Images';

    public function i18n_singular_name()
    {
        return _t('ImagesWithStyleSelection.SINGULAR_NAME', 'Selection of Images');
    }

    private static $plural_name = 'Selections of Images';

    public function i18n_plural_name()
    {
        return _t('ImagesWithStyleSelection.PLURAL_NAME', 'Selections of Images');
    }


    #######################
    ### Model Section
    #######################

    private static $db = [
        'Title' => 'Varchar(255)', // this needs to be lengthy to avoid the same names ...
        'Description' => 'Text'
    ];

    private static $has_one = [
        'PlaceToStoreImages' => 'Folder'
    ];

    private static $many_many = [
        'StyledImages' => 'ImageWithStyle'
    ];

    private static $many_many_extraFields = [
        'StyledImages' => [
            'SortOrder' => 'Int',
        ]
    ];



    #######################
    ### Further DB Field Details
    #######################

    private static $indexes = [
        'Created' => true,
        'Title' => 'unique("Title")'
    ];

    private static $defaults = [
        'Title' => ''
    ];

    private static $default_sort = [
        'Created' => 'DESC'
    ];

    private static $required_fields = [
        'Title'
    ];

    private static $searchable_fields = [
        'Title' => 'PartialMatchFilter'
    ];


    #######################
    ### Field Names and Presentation Section
    #######################

    private static $field_labels = [
        'StyledImages' => 'Images to be included'
    ];

    private static $field_labels_right = [
        'StyledImages' => 'Select as many as you like and sort them in the right order'
    ];

    private static $summary_fields = [
        'Title' => 'Name',
        'StyledImages.Count' => 'Number of Images'
    ];


    #######################
    ### Casting Section
    #######################


    #######################
    ### can Section
    #######################



    #######################
    ### write Section
    #######################




    public function validate()
    {
        $result = parent::validate();
        $fieldLabels = $this->FieldLabels();
        $indexes = $this->Config()->get('indexes');
        $requiredFields = $this->Config()->get('required_fields');
        if (is_array($requiredFields)) {
            foreach ($requiredFields as $field) {
                $value = $this->$field;
                if (! $value) {
                    $fieldWithoutID = $field;
                    if (substr($fieldWithoutID, -2) === 'ID') {
                        $fieldWithoutID = substr($fieldWithoutID, 0, -2);
                    }
                    $myName = isset($fieldLabels[$fieldWithoutID]) ? $fieldLabels[$fieldWithoutID] : $fieldWithoutID;
                    $result->error(
                        _t(
                            'ImagesWithStyleSelection.'.$field.'_REQUIRED',
                            $myName.' is required'
                        ),
                        'REQUIRED_ImagesWithStyleSelection_'.$field
                    );
                }
                if (isset($indexes[$field]) && isset($indexes[$field]['type']) && $indexes[$field]['type'] === 'unique') {
                    $id = (empty($this->ID) ? 0 : $this->ID);
                    $count = ImagesWithStyleSelection::get()
                        ->filter(array($field => $value))
                        ->exclude(array('ID' => $id))
                        ->count();
                    if ($count > 0) {
                        $myName = $fieldLabels['$field'];
                        $result->error(
                            _t(
                                'ImagesWithStyleSelection.'.$field.'_UNIQUE',
                                $myName.' needs to be unique'
                            ),
                            'UNIQUE_ImagesWithStyleSelection_'.$field
                        );
                    }
                }
            }
        }

        return $result;
    }


    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if ($this->exists() && $this->PlaceToStoreImagesID) {
            $allImages = Image::get()->filter(['ParentID' => $this->PlaceToStoreImagesID])->column('ID');
            $existingImages = $this->RawImages()->column('ID');
            $difference = array_diff($allImages, $existingImages);
            $list = $this->StyledImages();
            if (count($difference)) {
                foreach ($difference as $imageID) {
                    $image = Image::get()->byID($imageID);
                    if ($image) {
                        $styledImage = ImageWithStyle::create();
                        $styledImage->Title = $image->Name;
                        $styledImage->ImageID = $imageID;
                        $styledImage->StyleID = ImageStyle::get_default_style()->ID;
                        $styledImage->write();
                        $list->add($styledImage);
                    }
                }
            }
        }
        //...
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        //...
    }


    #######################
    ### Import / Export Section
    #######################

    public function getExportFields()
    {
        //..
        return parent::getExportFields();
    }



    #######################
    ### CMS Edit Section
    #######################


    public function CMSEditLink()
    {
        $controller = singleton("ImageWithStyleAdmin");

        return $controller->Link().$this->ClassName."/EditForm/field/".$this->ClassName."/item/".$this->ID."/edit";
    }

    public function CMSAddLink()
    {
        $controller = singleton("ImageWithStyleAdmin");

        return $controller->Link().$this->ClassName."/EditForm/field/".$this->ClassName."/item/new";
    }


    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('PlaceToStoreImages');
        $fields->addFieldToTab(
            'Root.Main',
            $treeField = TreeDropdownField::create(
                'PlaceToStoreImagesID',
                'Image Folder',
                'Folder'
            )->setRightTitle('Optional - set folder ... all images in this folder will automatically be added.')
        );
        if ($this->PlaceToStoreImagesID) {
            $folder = $this->PlaceToStoreImages();
            if ($folder && $folder->exists()) {
                $rightFieldTitle = $treeField->RightTitle();
                $rightFieldTitle .= '
                    <br  /><a href="/admin/assets/show/'.$folder->ID.'/" target="_blank">add images directly to folder</a>
                    <br />After you have updated the folder make sure to save this list to receive the latest updates.';
                $treeField->setRightTitle($rightFieldTitle);
            }
        }
        //do first??
        $rightFieldDescriptions = $this->Config()->get('field_labels_right');
        foreach ($rightFieldDescriptions as $field => $desc) {
            $formField = $fields->DataFieldByName($field);
            if (! $formField) {
                $formField = $fields->DataFieldByName($field.'ID');
            }
            if ($formField) {
                $formField->setDescription($desc);
            }
        }
        //...
        if ($this->exists()) {
            $config = GridFieldConfig_RelationEditor::create();
            $config->addComponent(new GridFieldSortableRows('SortOrder'));
            $fields->removeByName('StyledImages');
            $fields->addFieldToTab(
                    'Root.Images',
                    GridField::create(
                        'StyledImages',
                        'Images',
                        $this->StyledImages(),
                        $config
                    )
                );
        }
        return $fields;
    }

    /**
     * @return DataList
     */
    public function RawImages()
    {
        $array = [];
        if ($this->StyledImages()->count()) {
            foreach ($this->StyledImages()->column('ImageID') as $id) {
                if ($id) {
                    $array[$id] = $id;
                }
            }
        }

        return Image::get()->filter(['ID' => $array]);
    }

    /**
     * force the list to use a certain folder ...
     * @param  string $folderName
     * @return ImagesWithStyleSelection
     */
    public function createFolder($folderName)
    {
        $folder = Folder::find_or_make($folderName);
        $this->PlaceToStoreImagesID = $folder->ID;
        $folder->write();

        return $this;
    }
}
