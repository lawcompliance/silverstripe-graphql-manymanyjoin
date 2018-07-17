<?php

namespace Internetrix\GraphQLManyManyJoin;

use GraphQL\Type\Definition\ObjectType;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Util\ScaffoldingUtil;

class DataObjectScaffolderExtension extends Extension
{
    public function onAfterAddToManager(Manager $manager){

        $relationsToJoin = Config::inst()->get('Internetrix\GraphQLManyManyJoin\DataObjectScaffolderExtension',
            'list_items');

        if(!empty($relationsToJoin)){
            foreach($relationsToJoin as $listObject => $throughObject){
                if($this->owner->getDataObjectClass() == $listObject){
                    $this->owner->addField('Join');
                    if($manager->hasType($this->owner->typeName())){
                        $fields = $manager->getType($this->owner->typeName())->getFields();
                        $type =  new ObjectType(
                            [
                                'name' => $this->owner->typeName(),
                                'fields' => function () use ($manager, $fields, $throughObject) {
                                    $fields['Join'] = [
                                        'type' => $manager->getType(ScaffoldingUtil::typeNameForDataObject($throughObject))
                                    ];
                                    return $fields;
                                },
                            ]
                        );
                        $manager->addType($type, $this->owner->typeName());
                    }
                }
            }
        }
    }
}