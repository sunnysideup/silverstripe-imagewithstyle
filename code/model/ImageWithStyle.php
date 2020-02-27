<?php



class ImageWithStyle extends DataObject
{

    #######################
    ### Names Section
    #######################

    private static $singular_name = 'Image With Style';

    public function i18n_singular_name()
    {
        return _t('ImageWithStyle.SINGULAR_NAME', 'Image With Style');
    }

    private static $plural_name = 'Images With Style';

    public function i18n_plural_name()
    {
        return _t('ImageWithStyle.PLURAL_NAME', 'Images With Style');
    }


    #######################
    ### Model Section
    #######################

    private static $db = [
        'Title' => 'Varchar(100)',
        'Description' => 'Text',
        'AlternativeImageURL' => 'Varchar(255)', //e.g. https://via.placeholder.com/468x60?text=hellooooooooooooooo
        'VideoLink' => 'Varchar(255)',
        'Var1' => 'Varchar',
        'Var2' => 'Varchar',
        'Var3' => 'Varchar',
        'Var4' => 'Varchar',
        'Var5' => 'Varchar',
    ];

    private static $has_one = [
        'Image' => 'Image',
        'Style' => 'ImageStyle',
        'LinksTo' => 'SiteTree',
    ];

    private static $belongs_many_many = [
        'Selections' => 'ImagesWithStyleSelection'
    ];


    #######################
    ### Further DB Field Details
    #######################

    private static $indexes = [
        'Created' => true
    ];

    private static $default_sort = [
        'Created' => 'DESC'
    ];

    private static $required_fields = [
        'Title',
        'StyleID'
    ];

    private static $searchable_fields = [
        'Title' => 'PartialMatchFilter'
    ];


    #######################
    ### Field Names and Presentation Section
    #######################

    private static $field_labels = [
        'Style' => 'Display Style',
        'Title' => 'Image Title',
        'Selections' => 'Lists'
    ];

    private static $field_labels_right = [
        'Pages' => 'On what pages is this image visible'
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'Style.Title' => 'Style',
        'Image.CMSThumbnail' => 'Image'
    ];


    private static $casting = [
        'ImageElement' => 'HTMLText'
    ];

    private static $unique_vars = ['Var1', 'Var2', 'Var3', 'Var4', 'Var5'];


    #######################
    ### Casting Section
    #######################


    #######################
    ### can Section
    #######################

    public function canDelete($member = null)
    {
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
        if($this->exists() && $this->hasRealStyle()) {
            if($this->AlternativeImageURL) {
                $requiredFields[] = 'AlternativeImageURL';
            } else {
                $requiredFields[] = 'ImageID';
            }
        }
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
                            'ImageWithStyle.'.$field.'_REQUIRED',
                            $myName.' is required'
                        ),
                        'REQUIRED_ImageWithStyle_'.$field
                    );
                }
                if (isset($indexes[$field]) && isset($indexes[$field]['type']) && $indexes[$field]['type'] === 'unique') {
                    $id = (empty($this->ID) ? 0 : $this->ID);
                    $count = ImageWithStyle::get()
                        ->filter(array($field => $value))
                        ->exclude(array('ID' => $id))
                        ->count();
                    if ($count > 0) {
                        $myName = $fieldLabels['$field'];
                        $result->error(
                            _t(
                                'ImageWithStyle.'.$field.'_UNIQUE',
                                $myName.' needs to be unique'
                            ),
                            'UNIQUE_ImageWithStyle_'.$field
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
        if (!$this->StyleID) {
            $this->StyleID = $defaultStyle->ID;
        }
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();
        //...
        $image = $this->Image();
        if ($image && $image->exists()) {
            $image->Title = $this->Title;
            $image->write();
        }
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
        //...
        if ($this->hasRealStyle()) {
            $style = $this->Style();
            $varsArray = $this->Config()->get('unique_vars');
            //var_dump($this->Style());
            foreach ($varsArray as $varName) {
                if ($this->Style()->hasStyleVariable($varName)) {
                    $name = $varName . 'Name';
                    $type = $varName . 'Type';
                    $descriptionField = $varName . 'Description';
                    switch ($this->Style()->$type) {
                        case 'Pixels':
                        case 'Percentage':
                            $newField = NumericField::create($varName, $style->$name)
                                ->setRightTitle($this->Style()->$descriptionField.' ('.$style->$type.'). Negative number are allowed in some instances.');
                            break;
                        case 'Options':
                            $newField = OptionSetField::create($varName, $style->$name, $this->Style()->OptionsAsArray($varName))
                                ->setRightTitle($this->Style()->$descriptionField);
                            break;
                    }
                    $fields->replaceField(
                        $varName,
                        $newField
                    );
                } else {
                    $fields->removeByName($varName);
                }
            }
            if ($style->ClassNameForCSS) {
                $fields->replaceField(
                    'Image',
                    $uploadField = PerfectCMSImagesUploadField::create('Image', 'Image', null, $style->ClassNameForCSS)
                );
                $uploadField->setFolderName($this->BestFolder());
            }
        } else {
            $varsArray = $this->Config()->get('unique_vars');
            foreach ($varsArray as $var) {
                $fields->removeByName($var);
            }
            $fields->replaceField(
                'Image',
                $uploadField = LiteralField::create('ImageReminder', '<h2>Image Upload</h2><p>Please select a style first (see below) and save this record. After that you can upload an image</p>')
            );
        }

        $fields->replaceField(
            'Selections',
            CheckboxSetField::create(
                'Selections',
                'Included in ...',
                ImagesWithStyleSelection::get()->map()
            )
        );

        $fields->replaceField(
            'LinksToID',
            TreeDropdownField::create(
                'LinksToID',
                'LinksTo',
                'SiteTree'
            )
        );

        return $fields;
    }

    /**
     * @return string (HTML)
     */
    public function ImageElement()
    {
        return $this->getImageElement();
    }


    /**
     * @return string (HTML)
     */
    public function getImageElement()
    {
        $html = '';
        if ($this->hasRealStyle()) {
            if ($this->hasImageOrAlternativeImage()) {
                $style = $this->Style();
                $array = [
                    'Styles' => $this->buildStyles(),
                    'ClassNameForCSS' => $style->ClassNameForCSS,
                    'ImageTag' => $this->getImageTag(),
                    'Caption' => $this->Description,
                    'ImageObject' => $this->Image(),
                    'LinksTo' => $this->LinksTo(),
                    'YouTubeVideoThumbnailURL' => $this->getYouTubeVideoThumbnailURL(),
                    'VideoLink' => $this->VideoLink,
                ];
                return ArrayData::create($array)
                    ->renderWith('ImageWithStyle');
            }
        }
        return $html;
    }

    public function getImageTag()
    {
        if($this->AlternativeImageURL) {
            return '<img src="'.$this->AlternativeImageURL.'" alt="'.Convert::raw2att($this->Title).'" />';
        } else {
            return $this->Image()->PerfectCMSImageTag($this->Style()->ClassNameForCSS);
        }
    }

    public function getYouTubeVideoThumbnailURL() {
        if (!$this->VideoLink) {
            return null;
        }
        else {
            // this regular expression could be quite fragile IDK
            // YouTube video URL IDs are NOT guaranteed to always be 11 characters long...
            $youTubeVideoIDRegex = preg_match('/v=\w{11}/', $this->VideoLink, $matches);
            if (count($matches) == 0) {
                throw new Exception('Could not find the ID from the VideoLink');
            }
            // remove the "v=" from the front of the result
            return substr($matches[0], 2);
        }
    }

    public function hasImageOrAlternativeImage() : bool
    {
        if($this->AlternativeImageURL) {
            return true;
        } else {
            return $image && $image->exists();
        }
    }


    /**
     * @return string (HTML)
     */
    public function buildStyles()
    {
        $styles = '';
        if ($this->hasRealStyle()) {
            $style = $this->Style();
            $stylesArray = [];
            $varsArray = $this->Config()->get('unique_vars');
            ;
            //var_dump($this->Style());
            foreach ($varsArray as $var) {
                if ($this->$var) {
                    $name = $var . 'Name';
                    $type = $var . 'Type';
                    $styleString = $style->$name . ': ' . $this->$var . $this->convertVarTypeToCSS($style->$type) . ';';
                    array_push($stylesArray, $styleString);
                }
            }
            if (! empty($stylesArray)) {
                $styles = ' style="'.implode(" ", $stylesArray).'"';
            }
        }
        return $styles;
    }

    protected function hasRealStyle()
    {
        if ($this->StyleID) {
            $style = $this->Style();
            return $style->exists();
        }

        return false;
    }

    /**
     * @param string
     * @return string
     */
    protected function convertVarTypeToCSS($type)
    {
        $str = '';
        switch ($type) {
            case 'Pixels':
                $str = 'px';
                break;
            case 'Percentage':
                $str = '%';
                break;
            case 'Options':
                break;
        }
        return $str;
    }

    public function BestFolder()
    {
        if ($this->Selections()->count()) {
            foreach ($this->Selections() as $selection) {
                if ($folder = $selection->PlaceToStoreImages()) {
                    if ($folder->FileName) {
                        return trim(
                            str_replace(ASSETS_DIR, '', $folder->Filename),
                            '/'
                        );
                    }
                }
            }
        }
    }
}
