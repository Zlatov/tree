<?php
namespace Zlatov\Tree;

/**
* Tree
*/
trait TraitTree
{

    private static $default_tree_options = [
        'root_id' => 0,

        'field_id'        => 'id',
        'field_pid'       => 'pid',
        'field_level'     => 'level',
        'field_header'    => 'name',
        'field_childrens' => 'childrens',
        'only_fields' => null, // Массив полей необходимых пользователю

        'tpl_ul_main' => '<ul class="tree">',
        'tpl_ul'      => '<ul>',
        'tpl_ul_end'  => '</ul>',
        'tpl_li'      => '<li>??header?? <small>#??id??</small>',
        'tpl_li_end'  => '</li>',

        'forSelect' => false,
        'addRoot' => false, // В массив для тега select добавить первый option со значением "без родителя"
        'rootName' => 'Нет родителя (этот элемент корневой)',

        'clearFromNonRoot' => true,
    ];

    public static function init()
    {
        foreach (self::$default_tree_options as $key => $value) {
            if (preg_match('/^tpl_/', $key)==1) {
                self::$default_tree_options[$key].= PHP_EOL;
            }
        }
    }

    public static function tree_get_options()
    {
        return array_merge(self::$default_tree_options, self::$tree_options);
    }

    public static function tree_all()
    {
        $table_name = self::tree_get_options()['table_name'];
        return self::tree_get_pdo()->query("CALL ${table_name}_tree_all;")->fetchAll();
    }

    // private static function merge_options($options)
    // {
    //     return array_merge(self::get_options(), $options);
    // }

    private static function tree_get_pdo()
    {
        return $GLOBALS[self::tree_get_options()['pdo']];
    }

    public static function to_nested($array, $options = [])
    {
        $options = self::tree_get_options();
        $return = [];
        $cache = [];
        // Для каждого элемента
        foreach ($array as $key => $value) {
            // Если нет родителя элемента, и элемент не корневой,
            // тогда создаем родителя в возврат а ссылку в кэш
            if (!isset($cache[$value[$options['field_pid']]]) && ($value[$options['field_pid']] != $options['root_id'])) {
                if ($options['only_fields'] === null) {
                    $temp = array_fill_keys(array_keys($value), null);
                } else {
                    $temp = [];
                    foreach ($options['only_fields'] as $fieldName) {
                        $temp[$fieldName] = $value[$fieldName];
                    }
                }
                $temp[$options['field_id']] = $value[$options['field_pid']];
                $temp[$options['field_pid']] = null;
                $temp[$options['field_childrens']] = [];
                $return[$value[$options['field_pid']]] = $temp;
                $cache[$value[$options['field_pid']]] = &$return[$value[$options['field_pid']]];
            }
            // Если элемент уже был создан, значит он был чьим-то родителем, тогда
            // обновим в нем информацию о его родителе и все остальное
            if (isset($cache[$value[$options['field_id']]])) {
                if ($options['only_fields'] === null) {
                    $temp = $value;
                } else {
                    $temp = [];
                    foreach ($options['only_fields'] as $fieldName) {
                        $temp[$fieldName] = $value[$fieldName];
                    }
                }
                $temp[$options['field_id']] = $value[$options['field_id']];
                $temp[$options['field_pid']] = $value[$options['field_pid']];
                unset($temp[$options['field_childrens']]); // Кроме чилдренов, а то можем стереть данные детей
                foreach ($temp as $fieldName => $fieldValue) {
                    $cache[$value[$options['field_id']]][$fieldName] = $fieldValue;
                }
                // Если этот элемент не корневой,
                // тогда переместим его в родителя, и обновим ссылку в кэш
                if ($value[$options['field_pid']] != $options['root_id']) {
                    $cache[$value[$options['field_pid']]][$options['field_childrens']][$value[$options['field_id']]] = $return[$value[$options['field_id']]];
                    unset($return[$value[$options['field_id']]]);
                    $cache[$value[$options['field_id']]] = &$cache[$value[$options['field_pid']]][$options['field_childrens']][$value[$options['field_id']]];
                }
            }
            // Иначе, элемент новый, родитель уже создан, добавим в родителя
            else {
                // Если элемент не корневой - вставляем в родителя беря его из кэш
                if ($value[$options['field_pid']] != $options['root_id']) {
                    if ($options['only_fields'] === null) {
                        $temp = $value;
                    } else {
                        $temp = [];
                        foreach ($options['only_fields'] as $fieldName) {
                            $temp[$fieldName] = $value[$fieldName];
                        }
                    }
                    $temp[$options['field_childrens']] = [];
                    // Берем родителя из кэш и вставляем в "детей"
                    $cache[$value[$options['field_pid']]][$options['field_childrens']][$value[$options['field_id']]] = $temp;
                    // Вставляем в кэш ссылку на элемент
                    $cache[$value[$options['field_id']]] = &$cache[$value[$options['field_pid']]][$options['field_childrens']][$value[$options['field_id']]];
                }
                // Если элемент кокренвой, вставляем сразу в return и в кэш ссылку
                else {
                    if ($options['only_fields'] === null) {
                        $temp = $value;
                    } else {
                        $temp = [];
                        foreach ($options['only_fields'] as $fieldName) {
                            $temp[$fieldName] = $value[$fieldName];
                        }
                    }
                    $temp[$options['field_childrens']] = [];
                    $return[$value[$options['field_id']]] = $temp;
                    // Вставляем в кэш ссылку на элемент
                    $cache[$value[$options['field_id']]] = &$return[$value[$options['field_id']]];
                }
            }
        }
        if ($options['clearFromNonRoot']) {
            foreach ($return as $key => $value) {
                if ($value[$options['field_header']] === null) {
                    unset($return[$key]);
                }
            }
        }
        return $return;
    }

    function to_html($array, $options = [])
    {
        $options = self::tree_get_options();
        $replace_fields = self::get_tag_key($options['tpl_li']);

        $html = $options['tpl_ul_main'];
        $level = 0;
        $parentArray[$level] = $array;
        while ($level >= 0) {
            $mode = each($parentArray[$level]);
            if ($mode !== false) {
                $replace = [];
                foreach ($replace_fields as $tag_key) {
                    $replace["??$tag_key??"] = (isset($mode[1][$tag_key]))?$mode[1][$tag_key]:'';
                }
                $html .= str_repeat("    ", $level*2 + 1) . str_replace(array_keys($replace),$replace,$options['tpl_li']);
                if (count($mode[1]['childrens'])) {
                    $level++;
                    $parentArray[$level] = $mode[1]['childrens'];
                    $html .= str_repeat("    ", $level*2) . $options['tpl_ul'];
                } else {
                    $html .= str_repeat("    ", $level*2 + 1) . $options['tpl_li_end'];
                }
            } else {
                $html .= ($level>0)?str_repeat("    ", ($level)*2) . $options['tpl_ul_end']:$options['tpl_ul_end'];
                $html .= ($level>0)?str_repeat("    ", ($level)*2 - 1) . $options['tpl_li_end']:'';
                $level--;
            }
        }
        return $html;
    }

    public static function get_tag_key($tpl_li='')
    {
        if (empty($tpl_li)) {
            $tpl_li = self::get_options()['tpl_li'];
        }
        $pattern = '/\?\?(\w+)\?\?/';
        $subject = $tpl_li;
        preg_match_all($pattern, $subject, $matches);
        return $matches[1];
    }

}

TraitTree::init();
