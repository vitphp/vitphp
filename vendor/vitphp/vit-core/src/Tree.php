<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------

namespace vitphp\admin;

/**
 * 树形操作
 * Class Tree
 * @package LiteAdmin
 */
class Tree
{
    /**
     * 数组转树
     * @param $array
     * @param string $pk
     * @param string $pkey
     * @param string $skey
     * @return array
     */
    public static function array2tree($array,$pk='id',$pkey='pid',$skey='_child') {
        $list = [];
        $tree = [];
        foreach ($array as $item){
            $list[$item[$pk]] = $item;
        }
        foreach ($list as &$item){
            if (isset($list[$item[$pkey]])){
                $list[$item[$pkey]][$skey][] = &$item;
            }else{
                if ($item[$pkey] == 0){
                    $tree[] = &$item;
                }
            }
        }
        return $tree;
    }

    /**
     * 树转列表
     * @param $tree
     * @param string $skey
     * @return array
     */
    public static function tree2list($tree,$skey='_child',$prefix='_pre',$level=0){
        $array = [];
        foreach ($tree as $item){
            $item[$prefix] = str_repeat('　｜',$level).($level?'—':'');
            if (isset($item[$skey])){
                $child = self::tree2list($item[$skey],$skey,$prefix,$level+1);
                unset($item[$skey]);
                $array[] = $item;
                $array = array_merge($array, $child);
            }else{
                $array[] = $item;
            }
        }
        return $array;
    }

    /**
     * 数组转列表
     * @param $array
     * @param string $pk
     * @param string $pkey
     * @param string $skey
     * @return array
     */
    public static function array2list($array,$pk='id',$pkey='pid',$skey='child'){
        $tree = self::array2tree($array, $pk, $pkey, $skey);
        return self::tree2list($tree, $skey);
    }

}