<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 石家庄萌折科技有限公司 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/ 以获得更多细节。
// +----------------------------------------------------------------------

namespace app;

class ExcelExtend
{
    protected $handle;

    function __construct($filename)
    {
        $filename = $filename . '.csv';
        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename=" . iconv('utf-8', 'gbk//TRANSLIT', $filename));
        $this->handle = fopen('php://output', 'w');
    }

    /**
     * 设置标题行
     * 此方法应该优先调用
     * @param $title
     */
    public function setTitle($title)
    {
        if(!is_array($title)){
            $title = [$title];
        }
        fputcsv($this->handle, $title);
    }

    /**
     * 设置内容
     * 每调用一次会自动在最后一行开始插入
     * 最后一次执行此方法的时候，会导出csv格式文件
     * @param array $list 二维数组
     */
    public function setData($list)
    {
        foreach ($list as $data) {
            fputcsv($this->handle, $data);
        }
    }

    public function end()
    {
        exit();
    }

    function __destruct(){
        fclose($this->handle);
    }
}