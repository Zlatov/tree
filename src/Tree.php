<?php
namespace Zlatov\Tree;

/**
* Tree
*/
class Tree
{
    // function __construct()
    // {
    // }

    public static function convertIntoMultidimensionalArray($array, $idOfTheRoot = 0, $nameOfTheParentKey = 'pid')
    {
        $return = [];
        $cache = [];
        // Для каждого элемента
        foreach ($array as $key => $value) {
            // Если нет родителя элемента, и элемент не корневой,
            // тогда создаем родителя в возврат а ссылку в кэш
            if (!isset($cache[$value[$nameOfTheParentKey]]) && ($value[$nameOfTheParentKey] != $idOfTheRoot)) {
                $return[$value[$nameOfTheParentKey]] = [
                    'id' => $value[$nameOfTheParentKey],
                    $nameOfTheParentKey => null,
                    'childrens' => [],
                ];
                $cache[$value[$nameOfTheParentKey]] = &$return[$value[$nameOfTheParentKey]];
            }
            // Если элемент уже был создан, значит он был чьим-то родителем, тогда
            // обновим в нем информацию о его родителе
            if (isset($cache[$value['id']])) {
                $cache[$value['id']][$nameOfTheParentKey] = $value[$nameOfTheParentKey];
                // Если этот элемент не корневой,
                // тогда переместим его в родителя, и обновим ссылку в кэш
                if ($value[$nameOfTheParentKey] != $idOfTheRoot) {
                    $cache[$value[$nameOfTheParentKey]]['childrens'][$value['id']] = $return[$value['id']];
                    unset($return[$value['id']]);
                    $cache[$value['id']] = &$cache[$value[$nameOfTheParentKey]]['childrens'][$value['id']];
                }
            }
            // Иначе, элемент новый, родитель уже создан, добавим в родителя
            else {
                // Если элемент не корневой - вставляем в родителя беря его из кэш
                if ($value[$nameOfTheParentKey] != $idOfTheRoot) {
                    // Берем родителя из кэш и вставляем в "детей"
                    $cache[$value[$nameOfTheParentKey]]['childrens'][$value['id']] = [
                        'id' => $value['id'],
                        $nameOfTheParentKey => $value[$nameOfTheParentKey],
                        'childrens' => [],
                    ];
                    // Вставляем в кэш ссылку на элемент
                    $cache[$value['id']] = &$cache[$value[$nameOfTheParentKey]]['childrens'][$value['id']];
                }
                // Если элемент кокренвой, вставляем сразу в return
                else {
                    $return[$value['id']] = [
                        'id' => $value['id'],
                        $nameOfTheParentKey => $value[$nameOfTheParentKey],
                        'childrens' => [],
                    ];
                    // Вставляем в кэш ссылку на элемент
                    $cache[$value['id']] = &$return[$value['id']];
                }
            }
            memoryUsage(memory_get_usage(), 'in foreach Multi', false);
        }
        return $return;
    }

}
