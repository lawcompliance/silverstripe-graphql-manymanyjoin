<?php

namespace Internetrix\GraphQLManyManyJoin\Extensions;

use GraphQL\Type\Definition\ObjectType;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;

class DataObjectScaffolderExtension extends Extension
{

    //TODO handle many_many_extraFields as well. Only handles through objects
    public function onAfterAddToManager(Manager $manager){

        $relationsToJoin = Config::inst()->get(DataObjectScaffolderExtension::class, 'list_items');

        if(!empty($relationsToJoin)){
            foreach($relationsToJoin as $listObject => $throughObject){
                if($this->owner->getDataObjectClass() == $listObject){
                    $this->owner->addField('Join');
                    $typeName = $this->owner->getTypeName();
                    $throughObjectTypeName = StaticSchema::inst()->typeNameForDataObject($throughObject);
                    if($manager->hasType($typeName) && $manager->hasType($throughObjectTypeName)){
                        $fields = $manager->getType($typeName)->getFields();
                        $type =  new ObjectType(
                            [
                                'name' => $typeName,
                                'fields' => function () use ($manager, $fields, $throughObjectTypeName) {
                                    $fields['Join'] = [
                                        'type' => $manager->getType($throughObjectTypeName)
                                    ];
                                    return $fields;
                                },
                            ]
                        );
                        $manager->addType($type, $typeName);
                    }
                }
            }
        }
    }
}