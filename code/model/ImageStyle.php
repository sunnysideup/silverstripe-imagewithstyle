<?php



class ImageStyle extends DataObject
{


    /**
     * see _config folder for details ...
     * @var array
     */
    private static $record_defaults = [];


    #######################
    ### Names Section
    #######################

    private static $singular_name = 'Image Style';

    public function i18n_singular_name()
    {
        return _t('ImageStyle.SINGULAR_NAME', 'Image Style');
    }

    private static $plural_name = 'Image Styles';

    public function i18n_plural_name()
    {
        return _t('ImageStyle.PLURAL_NAME', 'Image Styles');
    }


    #######################
    ### Model Section
    #######################

    private static $db = [
        'Title' => 'Varchar',
        'ClassNameForCSS' => 'Varchar',
        'Description' => 'Text',
        'Var1Name' => 'Varchar',
        'Var1Type' => 'Enum(\'Pixels,Percentage,Options\', \'Pixels\')',
        'Var1Options' => 'Varchar(200)',
        'Var1Description' => 'Varchar(200)',

        'Var2Name' => 'Varchar',
        'Var2Type' => 'Enum(\'Pixels,Percentage,Options\', \'Pixels\')',
        'Var2Options' => 'Varchar(200)',
        'Var2Description' => 'Varchar(200)',

        'Var3Name' => 'Varchar',
        'Var3Type' => 'Enum(\'Pixels,Percentage,Options\', \'Pixels\')',
        'Var3Options' => 'Varchar(200)',
        'Var3Description' => 'Varchar(200)',

        'Var4Name' => 'Varchar',
        'Var4Type' => 'Enum(\'Pixels,Percentage,Options\', \'Pixels\')',
        'Var4Options' => 'Varchar(200)',
        'Var4Description' => 'Varchar(200)',

        'Var5Name' => 'Varchar',
        'Var5Type' => 'Enum(\'Pixels,Percentage,Options\', \'Pixels\')',
        'Var5Options' => 'Varchar(200)',
        'Var5Description' => 'Varchar(200)',

    ];

    private static $has_many = [
        'ImagesWithStyle' => 'ImageWithStyle'
    ];


    #######################
    ### Further DB Field Details
    #######################

    private static $indexes = [
        'Title' => true
    ];

    private static $default_sort = [
        'Title' => 'ASC'
    ];

    private static $required_fields = [
        'Title',
        'ClassNameForCSS'
    ];

    private static $searchable_fields = [
        'Title' => 'PartialMatchFilter',
        'Description' => 'PartialMatchFilter',
        'ClassNameForCSS' => 'PartialMatchFilter'
    ];


    #######################
    ### Field Names and Presentation Section
    #######################

    private static $field_labels = [
        'Title' => 'Style',

        'Var1Name' => 'Variable 1 Label',
        'Var1Type' => 'Variable 1 Type',
        'Var1Options' => 'Variable 1 Options',
        'Var1Description' => 'Variable 1 Description',

        'Var2Name' => 'Variable 2 Label',
        'Var2Type' => 'Variable 2 Type',
        'Var2Options' => 'Variable 1 Options',
        'Var2Description' => 'Variable 1 Description',

        'Var3Name' => 'Variable 3 Label',
        'Var3Type' => 'Variable 3 Type',
        'Var3Options' => 'Variable 1 Options',
        'Var4Description' => 'Variable 1 Description',

        'Var4Name' => 'Variable 4 Label',
        'Var4Type' => 'Variable 4 Type',
        'Var4Options' => 'Variable 4 Options',
        'Var4Description' => 'Variable 4 Description',

        'Var5Name' => 'Variable 5 Label',
        'Var5Type' => 'Variable 5 Type',
        'Var5Options' => 'Variable 5 Options',
        'Var5Description' => 'Variable 5 Description',
    ];

    private static $field_labels_right = [];

    private static $summary_fields = [
        'Title' => 'Style',
        'ImagesWithStyle.Count' => 'Usage Count'
    ];


    #######################
    ### Casting Section
    #######################


    #######################
    ### can Section
    #######################

    public function canCreate($member = null)
    {
        return false;
    }

    public function canEdit($member = null)
    {
        //we block edits in CMS
        return parent::canEdit($member);
    }

    public function canDelete($member = null)
    {
        if ($this->ImagesWithStyle()->count() === 0) {
            return parent::canDelete($member);
        }

        return false;
    }



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
                            'ImageStyle.'.$field.'_REQUIRED',
                            $myName.' is required'
                        ),
                        'REQUIRED_ImageStyle_'.$field
                    );
                }
                if (isset($indexes[$field]) && isset($indexes[$field]['type']) && $indexes[$field]['type'] === 'unique') {
                    $id = (empty($this->ID) ? 0 : $this->ID);
                    $count = ImageStyle::get()
                        ->filter(array($field => $value))
                        ->exclude(array('ID' => $id))
                        ->count();
                    if ($count > 0) {
                        $myName = $fieldLabels['$field'];
                        $result->error(
                            _t(
                                'ImageStyle.'.$field.'_UNIQUE',
                                $myName.' needs to be unique'
                            ),
                            'UNIQUE_ImageStyle_'.$field
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
        $this->ClassNameForCSS = preg_replace('/\W+/', '-', strtolower(strip_tags($this->ClassNameForCSS)));
        if (! $this->ClassNameForCSS) {
            $this->ClassNameForCSS = 'image-with-style-'.$this->ID;
        }
        //...
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();
        //...
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        //...
        $defaults = $this->Config()->record_defaults;
        $currentOnes = array_flip(ImageStyle::get()->column('ID'));
        $imageNames = Config::inst()->get('PerfectCMSImageDataExtension', 'perfect_cms_images_image_definitions');
        foreach ($defaults as $defaultValues) {
            foreach ($defaultValues as $field => $value) {
                if (is_array($value)) {
                    $defaultValues[$field] = serialize($value);
                }
            }
            $obj = ImageStyle::get()->filter(['Title' => $defaultValues['Title']])->first();
            if (!$obj) {
                $obj = ImageStyle::create($defaultValues);
            } else {
                foreach ($obj->db() as $field => $type) {
                    $obj->$field = null;
                }
                foreach ($defaultValues as $field => $value) {
                    $obj->$field = $value;
                }
            }
            unset($currentOnes[$obj->ID]);
            $obj->write();
            if (! isset($imageNames[$obj->ClassNameForCSS])) {
                user_error('You need to define a perfect CMS image with the following name: '.$obj->ClassNameForCSS);
            }
        }
        foreach ($currentOnes as $id) {
            $obj = ImageStyle::get()->byID($id);
            if ($obj->canDelete()) {
                $obj->delete();
            }
        }
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
        $controller = singleton("tba");

        return $controller->Link().$this->ClassName."/EditForm/field/".$this->ClassName."/item/".$this->ID."/edit";
    }

    public function CMSAddLink()
    {
        $controller = singleton("tba");

        return $controller->Link().$this->ClassName."/EditForm/field/".$this->ClassName."/item/new";
    }


    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

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

        // move variables to their own tab
        for ($i = 1; $i < 6; $i++) {
            if ($this->HasStyleVariable('Var'.$i)) {
                $fieldsToAdd = [
                    $fields->dataFieldByName('Var'.$i.'Name'),
                    $fields->dataFieldByName('Var'.$i.'Type'),
                    $fields->dataFieldByName('Var'.$i.'Options'),
                    $fields->dataFieldByName('Var'.$i.'Description')
                ];
                $fields->addFieldsToTab(
                    'Root.Variable '.$i,
                    $fieldsToAdd
                );
            } else {
                $fields->removeByName('Var'.$i.'Name');
                $fields->removeByName('Var'.$i.'Type');
                $fields->removeByName('Var'.$i.'Options');
                $fields->removeByName('Var'.$i.'Description');
            }
        }
        $fields->removeByName('ImagesWithStyle');

        //make everything readonly
        foreach ($fields->saveableFields() as $field) {
            $fieldName = $field->getName();
            $oldField = $fields->dataFieldByName($fieldName);
            if ($oldField) {
                $newField = $oldField->performReadonlyTransformation();
                $fields->replaceField($fieldName, $newField);
            }
        }

        return $fields;
    }


    public function HasStyleVariable($varName)
    {
        $name = $varName.'Name';
        $type = $varName.'Type';
        $hasBase = $this->$name && $this->$type ? true : false;
        if ($this->$type === 'Options') {
            return $hasBase && is_array($this->OptionsAsArray($varName)) ? true : false;
        } else {
            return $hasBase;
        }
    }
    public function HasOptionsAsArray($varName)
    {
        $options = $this->OptionsAsArray($varName);

        return count($options) ? true : false;
    }

    public function OptionsAsArray($varName) : array
    {
        $options = $varName.'Options';
        $array = [];
        if ($this->$options) {
            $array = @unserialize($this->$options);
        }
        if (is_array($array)) {
            return $array;
        }

        return [];
    }
}
