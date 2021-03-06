<?php



class ImagesWithStyleSelection extends DataObject
{

    /**
     * creates a folder name for a specific page for a specific method.
     *
     * @param  DataObject  $owner
     * @param  string      $methodName
     * @param  string      $folderName - optional
     *
     * @return string
     */
    public static function create_folder_name($owner, string $methodName, ?string $folderName = '') : string
    {
        if($folderName) {
            return $folderName;
        } else {
            return $methodName.'-for-'.$owner->ClassName.'-'.$owner->ID;
        }
    }


    public static function folder_to_list_name(string $folderName) : string
    {
        return str_replace('/', '-', $folderName);
    }

    /**
     * creates a default image list for a specific owner and method.
     * @param  DataObject   $owner
     * @param  string       $methodName
     * @param  string       $folderName
     * @return ImagesWithStyleSelection
     */
    public static function create_or_update_default_entry($owner, string $methodName, ?string $folderName = '')
    {
        //we add plus ten because they are not always identical
        if (strtotime($owner->LastEdited) > (strtotime($owner->Created) + 5)) {
            if ($folderName === '') {
                $folderName = self::create_folder_name($owner);
            }
            $listName = self::folder_to_list_name($folderName);

            $array = [
                'Title' => $listName
            ];
            $obj = ImagesWithStyleSelection::get()->filter($array)->first();
            if (! $obj) {
                $obj = ImagesWithStyleSelection::create($array);
            }

            $obj->createFolder($folderName);
            $obj->write();

            return $obj;
        }
        return null;
    }

    private static $size_options = [
        'landscape' => '1000x600',
        'portrait' => '600x1000',
        'cube' => '1000x1000',
        'youtubeVideo' => 'https://www.youtube.com/watch?v=AAnzPa5YFLk',
        'vimeoVideo' => 'https://vimeo.com/108799588',
    ];

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

    public function StyledImages()
    {
        return $this->getManyManyComponents('StyledImages')->sort('SortOrder');
    }

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
        'Description' => 'Name',
        'StyledImages.Count' => 'Number of Images',
        'ImageMedley' => 'HTMLText',
    ];


    public function getImageMedley()
    {
        $html = '';
        foreach($this->StyledImages() as $imageHolder) {
            $newImage = '[Image Missing]';
            if($imageHolder && $imageHolder->exists()) {
                $newImage = '['.$imageHolder->Title.']';
                $image = $imageHolder->Image();
                if($image && $image->exists()) {
                    $newImage = '['.$image->Link().']';
                    $thumb = $image->CMSThumbnail();
                    if($thumb) {
                        $newImage = $thumb->getTag();
                    }
                }
            }
            $html .= $newImage.'<br />';
        }
        return DBField::create_field('HTMLText', $html);
    }

    #######################
    ### Casting Section
    #######################


    #######################
    ### can Section
    #######################

    public function canEdit($member = null)
    {
        if($this->exists()) {
            if($this->IsTest()) {
                return false;
            }
        }
        return parent::canEdit($member);
    }

    public function canDelete($member = null)
    {
        if($this->exists()) {
            if($this->IsTest()) {
                return false;
            }
        }
        return parent::canDelete($member);
    }

    protected function IsTest() : bool
    {
        $filter = self::FILTER_FOR_TEST_LIST;
        foreach($filter as $key => $value) {
            if($this->$key !== $value) {
                return false;
            }
        }

        return true;
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

    protected function setDefaultTitle()
    {
        if($this->exists() && ! $this->Title) {
            $this->Title = 'List # '.$this->ID;
        }
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $this->setDefaultTitle();

        if ($this->exists() && $this->PlaceToStoreImagesID) {
            $allImagesIDs = Image::get()->filter(['ParentID' => $this->PlaceToStoreImagesID])->column('ID');
            $existingImagesIDs = $this->RawImages()->column('ID');
            $difference = array_diff($allImagesIDs, $existingImagesIDs);
            if (count($difference)) {
                $list = $this->StyledImages();
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

    const FILTER_FOR_TEST_LIST = ['Title' => 'TEST LIST'];

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        $emptyOnes = ImagesWithStyleSelection::get()->where('"Title" IS NULL OR Title = \'\'');
        foreach($emptyOnes as $item) {
            DB::alteration_message('Fixing up Titles for '.$item->ID);
            $item->setDefaultTitle();
            $item->write();
        }

        $stylesCompleted = [];
        // set up a test list
        $filter = self::FILTER_FOR_TEST_LIST;
        $list = DataObject::get_one('ImagesWithStyleSelection', $filter);
        if(! $list) {
            $list = ImagesWithStyleSelection::create($filter);
            $list->write();
        }
        $styles = ImageStyle::get();
        $styledImages = $list->StyledImages();
        foreach($styledImages as $image) {
            $styledImages->removeByID($image->ID);
            $image->delete();
        }
        foreach ($styles as $style) {
            if(! isset($stylesCompleted[$style->ClassNameForCSS])) {
                DB::alteration_message('Creating - '.$style->ClassNameForCSS);
                $stylesCompleted[$style->ClassNameForCSS] = true;
                foreach ($this->config()->get('size_options') as $sizeOptionName => $sizeOptionSizes){
                    if($sizeOptionName === 'youtubeVideo' or $sizeOptionName === 'vimeoVideo') {
                        $link = $sizeOptionSizes;
                        $filter = [
                            'VideoLink' => $link,
                            'StyleID' => $style->ID,
                        ];
                        $image = DataObject::get_one('ImageWithStyle', $filter);
                        if(! $image) {
                            $image = ImageWithStyle::create($filter);
                        }
                        $image->Title = $link . ', STYLE: '.$style->Title;
                    } else {
                        list($width, $height) = explode('x', $sizeOptionSizes);
                        $width = intval($width);
                        $height = intval($height);
                        $name = $style->Title . ' (' . $style->ClassNameForCSS . ')-' . $sizeOptionName;
                        $link = $this->getPlaceholderImage($width, $height, $name);
                        $filter = ['AlternativeImageURL' => $link];
                        $image = DataObject::get_one('ImageWithStyle', $filter);
                        if(! $image) {
                            $image = ImageWithStyle::create($filter);
                        }
                        $image->Title = $style->Title . ' STYLE - '. $sizeOptionName.' IMAGE';
                    }
                    $image->StyleID = $style->ID;
                    DB::alteration_message('Creating / Updating '.$image->Title);
                    $image->write();
                    $list->StyledImages()->add($image);
                }
            } else {
                DB::alteration_message('Updating - '.$style->ClassNameForCSS);
                if(strpos($style->Title, 'DOUBLE-UP') === false) {
                    $style->Title = $style->Title . ' - DOUBLE-UP';
                    $style->write();
                }
            }
        }
    }

    protected function getPlaceholderImage($width, $height, $name) : string
    {
        $colour = $this->randomColour();
        $oppositeColour = $this->colourInverse($colour);

        return 'https://via.placeholder.com/' . $width. 'x'. $height . '/'.$colour.'/'.$oppositeColour.'/?text='.urlencode($name);
    }

    private function randomColour() : string
    {
        return $this->randomColourPart() . $this->randomColourPart() . $this->randomColourPart();
    }

    private function randomColourPart() : string
    {
        return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
    }

    private function colourInverse($color){
        $rgb = '';
        for ($x=0;$x<3;$x++){
            $c = 255 - hexdec(substr($color,(2*$x),2));
            $c = ($c < 0) ? 0 : dechex($c);
            $rgb .= (strlen($c) < 2) ? '0'.$c : $c;
        }
        return $rgb;
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

        ImagesWithStyleCMSAPI::add_links_to_folder_field($treeField, $this);

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
     * @return Int
     */
    public function ImageCount()
    {
        return $this->StyledImages()->count();
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
     *
     * @return ImagesWithStyleSelection
     */
    public function createFolder($folderName)
    {
        $folder = $this->PlaceToStoreImages();
        if($folder && $folder->exists()) {
            //do nothing
        } else {
            //only run for empty images
            if ($this->StyledImages()->count() == 0) {
                $folder = Folder::find_or_make($folderName);
                $this->PlaceToStoreImagesID = $folder->ID;
                if(! $this->PlaceToStoreImagesID) {
                    user_error('Could not add folder!'.$folderName);
                }
                $this->write();
            }
        }

        return $this;
    }
}
