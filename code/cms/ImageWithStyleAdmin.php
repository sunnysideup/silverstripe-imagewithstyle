<?php

class ImageWithStyleAdmin extends ModelAdmin
{

    private static $managed_models = [
        'ImagesWithStyleSelection',
        'ImageWithStyle',
        'ImageStyle'
    ];

    private static $url_segment = 'imageswithstyle'; // Linked as /admin/products/

    private static $menu_title = 'Styled Images';

    private static $menu_priority = 10;

    private static $menu_icon = '/userforms/images/sitetree_icon.png';


}
