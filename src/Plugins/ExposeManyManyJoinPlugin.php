<?php

namespace Internetrix\GraphQLManyManyJoin\Plugins;

use Exception;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\DataObject\FieldAccessor;
use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Interfaces\ModelQueryPlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\Type;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\FieldType\DBField;

class ExposeManyManyJoinPlugin implements ModelQueryPlugin
{
    const IDENTIFIER = 'exposeManyManyJoin';

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    /**
     * @param ModelQuery $query
     * @param Schema $schema
     * @param array $config
     * @throws Exception
     */
    public function apply(ModelQuery $query, Schema $schema, array $config = []): void
    {
        $instance = Injector::inst()->get($query->getModel()->getSourceClass());
        $this->addTypesToSchema($instance, $schema);
    }

    /**
     * @param DataObject $instance
     * @param Schema $schema
     * @throws Exception
     */
    private function addTypesToSchema(DataObject $instance, Schema $schema)
    {
        $databaseSchema = Injector::inst()->get(DataObjectSchema::class);
        $manyMany = $instance->ManyMany();


        foreach ($manyMany as $relationship => $class) {
            $through = null;
            if (is_array($class) && isset($class['through']) && isset($class['to'])) {
                $to = $class['to'];
                $through = $class['through'];
                $class = $databaseSchema->hasOneComponent($through, $to);
            } elseif(is_array($class)) {
                throw new Exception('Class is an array but the "through" class is not defined');
            }

            $classTypeName = $schema->getTypeNameForClass($class);
            $relationshipType = $schema->getModel($classTypeName);

            if ($relationshipType) {
                $fields = [];
                $join = $relationshipType->getFieldByName('_join');
                if ($join) {
                    $type = $schema->getType($join->getType());
                    $fields = $type->getFields();
                }

                if($through){
                    $throughType = $schema->getModel($schema->getTypeNameForClass($through));
                    if($throughType){
                        $fields[FieldAccessor::formatField($throughType->getName())] = [
                            'type' => $throughType->getName(),
                            'resolver' => [static::class, 'resolveThroughJoin'],
                        ];
                    }
                }else{
                    $extraFields = $databaseSchema->manyManyExtraFieldsForComponent($instance->ClassName, $relationship);

                    if ($extraFields) {
                        $relationshipInstance = Injector::inst()->get($class);
                        $classRelationshipType = $schema->findOrMakeType($schema->getTypeNameForClass($instance->ClassName).$relationship);

                        $rFields = $classRelationshipType->getFields();
                        foreach ($extraFields as $dbFieldName => $dbFieldType) {
                            $result = $relationshipInstance->obj($dbFieldName);
                            if ($result instanceof DBField) {
                                $rFields[FieldAccessor::formatField($dbFieldName)] = [
                                    'type' => $result->config()->get('graphql_type'),
                                ];
                            }
                        }
                        $classRelationshipType->setFields($rFields);
                        $schema->addType($classRelationshipType);
                        $fields[FieldAccessor::formatField($classRelationshipType->getName())] = [
                            'type' => $classRelationshipType->getName(),
                        ];
                    }
                }

                if(!empty($fields)){
                    $join = Type::create($classTypeName.'Join', [
                        'fields' => $fields,
                        'fieldResolver' => [static::class, 'resolveRelationshipJoin'],
                    ]);

                    $schema->addType($join);

                    $relationshipType->addField('_join', [
                        'type' => $join->getName(),
                        'resolver' => [static::class, 'resolveJoinType'],
                    ]);
                }
            }
        }
    }

    /**
     * We don't need to do anything to the data, just pass it through for the fields
     * @param $obj
     * @return DataObject
     */
    public static function resolveJoinType($obj): ?DataObject
    {
        return $obj;
    }

    /**
     * Format each of the fields on the DataObject
     * @param $obj
     * @return DataObject
     */
    public static function resolveRelationshipJoin($obj): ?DataObject
    {
        $map = $obj->toMap();
        foreach ($map as $key => $value) {
            $obj->{FieldAccessor::formatField($key)} = $value;
        }

        return $obj;
    }

    /**
     * Format each of the fields on the DataObject
     * @param $obj
     * @return DataObject
     */
    public static function resolveThroughJoin($obj): ?DataObject
    {
        return $obj->getJoin();
    }

}
