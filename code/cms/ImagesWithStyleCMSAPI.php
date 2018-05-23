<?php



class ImagesWithStyleCMSAPI extends Object
{
    public static function add_links_to_folder_field($formField, $folderOrImagesWithStyleList)
    {
        $folder = null;
        if ($folderOrImagesWithStyleList instanceof ImagesWithStyleSelection) {
            if ($folderOrImagesWithStyleList->PlaceToStoreImagesID) {
                $folder = $folderOrImagesWithStyleList->PlaceToStoreImages();
            }
        } elseif ($folderOrImagesWithStyleList instanceof Folder) {
            $folder = $folderOrImagesWithStyleList;
        } else {
            user_error('folderOrImagesWithStyleList param needs to be Folder or ImagesWithStyleSelection');
        }
        if ($folder && $folder->exists()) {
            $rightFieldTitle = $formField->RightTitle();
            $rightFieldTitle .= '
                <h5>Quick Links</h5>
                You can <a href="/admin/assets/add/?ID='.$folder->ID.'" target="_blank">add images directly</a> to the <a href="/admin/assets/show/'.$folder->ID.'/" target="_blank">folder</a>.
                <br /><strong>To see the latest updates to this folder, you may need to click save below.</strong>';
            $formField->setRightTitle($rightFieldTitle);
        }
    }

    private static $_folder_cache = [];

    public static function folder_change_start($object, $key, $folderName)
    {
        if (!isset(self::$_folder_cache[$object->ClassName.'_'.$object->ID])) {
            self::$_folder_cache[$object->ClassName.'_'.$object->ID] = [];
        }
        if (!isset(self::$_folder_cache[$object->ClassName.'_'.$object->ID][$key])) {
            self::$_folder_cache[$object->ClassName.'_'.$object->ID][$key] = [];
        }
        self::$_folder_cache[$object->ClassName.'_'.$object->ID][$key]['before'] = $folderName;
    }

    public static function folder_change_end($object, $key, $folderName, $list = null)
    {
        self::$_folder_cache[$object->ClassName.'_'.$object->ID][$key]['after'] = $folderName;
        self::check_for_folder_changes_and_migrate_images(
            self::$_folder_cache[$object->ClassName.'_'.$object->ID][$key],
            $list
        );
        self::$_folder_cache[$object->ClassName.'_'.$object->ID][$key]['before'] = $folderName;
    }


    protected static function check_for_folder_changes_and_migrate_images($keyDetail, $list)
    {
        if(isset($keyDetail['before']) && isset($keyDetail['after'])) {
            if ($keyDetail['before'] !== $keyDetail['after']) {
                $oldFolder = Folder::find_or_make($keyDetail['before']);
                $newFolder = Folder::find_or_make($keyDetail['after']);
                $oldImages = Image::get()->filter(['ParentID' => $oldFolder->ID]);
                if ($newFolder && $newFolder->exists()) {
                    if ($list && $list->exists()) {
                        $list->PlaceToStoreImagesID = $newFolder->ID;
                        $list->write();
                    }
                    if ($oldFolder && $oldFolder->exists()) {
                        if ($oldImages && $oldImages->count()) {
                            foreach ($oldImages as $oldImage) {
                                $oldImage->ParentID = $newFolder->ID;
                                $oldImage->write();
                            }
                            $oldFilesAny = File::get()->filter(['ParentID' => $oldFolder->ID]);
                            if ($oldFilesAny->count()) {
                                $oldFilesAny->delete();
                            }
                        }
                    }
                }
            }
        }
    }
}
