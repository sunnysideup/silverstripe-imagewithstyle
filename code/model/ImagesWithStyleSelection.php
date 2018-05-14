<?php



class ImagesWithStyleSelection extends DataObject
{


    #######################
    ### Names Section
    #######################

    private static $singular_name = 'Selection of Images';

    function i18n_singular_name()
    {
        return _t('ImagesWithStyleSelection.SINGULAR_NAME', 'Selection of Images');
    }

    private static $plural_name = 'Selections of Images';

    function i18n_plural_name()
    {
        return _t('ImagesWithStyleSelection.PLURAL_NAME', 'Selections of Images');
    }


    #######################
    ### Model Section
    #######################

    private static $db = [
        'Title' => 'Varchar',
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
        if(is_array($requiredFields)) {
            foreach($requiredFields as $field) {
                $value = $this->$field;
                if(! $value) {
                    $fieldWithoutID = $field;
                    if(substr($fieldWithoutID, -2) === 'ID') {
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
                    if($count > 0) {
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
        //...
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();
        if($this->PlaceToStoreImagesID) {
            $folder = $this->PlaceToStoreImages();
            $allImages = Image::get()->filter(['ParentID' => $this->PlaceToStoreImagesID]);
            $existingImages = $this->RawImages()->column('ID');
            $difference = array_diff($allImages, $existingImages);
            $list = $this->StyledImages();
            if(count($difference)) {
                foreach($difference as $imageID) {
                    $styledImages = ImageStyle::create();
                    $styledImage->ImageID = $imageID;
                    $styledImage->write();
                    $list->add($styledImage);
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

        //do first??
        $rightFieldDescriptions = $this->Config()->get('field_labels_right');
        foreach($rightFieldDescriptions as $field => $desc) {
           $formField = $fields->DataFieldByName($field);
           if(! $formField) {
            $formField = $fields->DataFieldByName($field.'ID');
           }
           if($formField) {
               $formField->setDescription($desc);
           }
        }
        //...
        if($this->exists()) {
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
        if($this->StyledImages()->count()) {
            foreach($this->StyledImages()->column('ImageID') as $id) {
                $array[$styledImage->ImageID] = $styledImage->ImageID;
            }
        }

        return Image::get()->filter(['ID' => $array]);
    }

}
