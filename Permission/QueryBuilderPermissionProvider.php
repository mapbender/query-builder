<?php

namespace Mapbender\QueryBuilderBundle\Permission;

use FOM\UserBundle\Security\Permission\AbstractResourceDomain;
use FOM\UserBundle\Security\Permission\GlobalPermissionProvider;

class QueryBuilderPermissionProvider implements GlobalPermissionProvider
{

    const CATEGORY_NAME = "query_builder";
    const PERMISSION_CREATE = "qb_create";
    const PERMISSION_EDIT = "qb_edit";
    const PERMISSION_DELETE = "qb_delete";

    public function getCategories(): array
    {
        return [self::CATEGORY_NAME => 'mb.querybuilder.permission.category'];
    }

    public function getPermissions(): array
    {
        return [
            self::PERMISSION_CREATE => [
                'category' => self::CATEGORY_NAME,
                'cssClass' => AbstractResourceDomain::CSS_CLASS_WARNING,
            ],
            self::PERMISSION_EDIT => [
                'category' => self::CATEGORY_NAME,
                'cssClass' => AbstractResourceDomain::CSS_CLASS_WARNING,
            ],
            self::PERMISSION_DELETE => [
                'category' => self::CATEGORY_NAME,
                'cssClass' => AbstractResourceDomain::CSS_CLASS_DANGER,
            ],
        ];
    }
}
