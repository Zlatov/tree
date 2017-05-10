<?php
namespace Zlatov\Tree;

/**
* Tree
*/
class Tree
{
    public static $options = [
        'root_id' => 0,
        'field_parent' => 'pid',
    ];

    public function merge_options($options)
    {
        return array_merge(self::$options, $options);
    }

    public static function to_nested($array, $options = [])
    {
        $options = self::merge_options($options);
        $return = [];
        $cache = [];
        // Для каждого элемента
        foreach ($array as $key => $value) {
            // Если нет родителя элемента, и элемент не корневой,
            // тогда создаем родителя в возврат а ссылку в кэш
            if (!isset($cache[$value[$options['field_parent']]]) && ($value[$options['field_parent']] != $options['root_id'])) {
                $return[$value[$options['field_parent']]] = [
                    'id' => $value[$options['field_parent']],
                    $options['field_parent'] => null,
                    'childrens' => [],
                ];
                $cache[$value[$options['field_parent']]] = &$return[$value[$options['field_parent']]];
            }
            // Если элемент уже был создан, значит он был чьим-то родителем, тогда
            // обновим в нем информацию о его родителе
            if (isset($cache[$value['id']])) {
                $cache[$value['id']][$options['field_parent']] = $value[$options['field_parent']];
                // Если этот элемент не корневой,
                // тогда переместим его в родителя, и обновим ссылку в кэш
                if ($value[$options['field_parent']] != $options['root_id']) {
                    $cache[$value[$options['field_parent']]]['childrens'][$value['id']] = $return[$value['id']];
                    unset($return[$value['id']]);
                    $cache[$value['id']] = &$cache[$value[$options['field_parent']]]['childrens'][$value['id']];
                }
            }
            // Иначе, элемент новый, родитель уже создан, добавим в родителя
            else {
                // Если элемент не корневой - вставляем в родителя беря его из кэш
                if ($value[$options['field_parent']] != $options['root_id']) {
                    // Берем родителя из кэш и вставляем в "детей"
                    $cache[$value[$options['field_parent']]]['childrens'][$value['id']] = [
                        'id' => $value['id'],
                        $options['field_parent'] => $value[$options['field_parent']],
                        'childrens' => [],
                    ];
                    // Вставляем в кэш ссылку на элемент
                    $cache[$value['id']] = &$cache[$value[$options['field_parent']]]['childrens'][$value['id']];
                }
                // Если элемент кокренвой, вставляем сразу в return
                else {
                    $return[$value['id']] = [
                        'id' => $value['id'],
                        $options['field_parent'] => $value[$options['field_parent']],
                        'childrens' => [],
                    ];
                    // Вставляем в кэш ссылку на элемент
                    $cache[$value['id']] = &$return[$value['id']];
                }
            }
        }
        return $return;
    }

}
