<?php

namespace QL\Ext;

use QL\Contracts\PluginContract;
use QL\QueryList;

/**
 * QueryList Plugin: Baidu searcher.
 * Class Baidu
 *
 * @package QL\Ext
 * @author CocaCoffee <CocaCoffee@vip.qq.com>
 */
class Baidu implements PluginContract
{
    
    /**
     * QueryList对象
     *
     * @var QL\QueryList
     */
    protected $queryList;
    
    /**
     * 搜索关键词
     *
     * @var string
     */
    protected $keyword;
    
    /**
     * 每页条数
     *
     * @var integer
     */
    protected $pageSize = 10;
    
    /**
     * HTTP选项
     *
     * @var array
     */
    protected $httpOpt = [];
    
    /**
     * 请求地址
     *
     * @var string
     */
    const API = 'https://www.baidu.com/s';
    
    /**
     * 采集规则
     *
     * @var array
     */
    const RULES = [
        'title' => [
            'h3',
            'text'
        ],
        'link' => [
            'h3>a',
            'href'
        ]
    ];
    
    /**
     * 切片选择器
     *
     * @var string
     */
    const RANGE = '.result';

    /**
     * 初始化QueryList对象
     *
     * @param QueryList $queryList
     * @param int $pageSize
     */
    public function __construct(QueryList $queryList, int $pageSize = null)
    {
        $this->queryList = $queryList->rules(self::RULES)->range(self::RANGE);
        $this->pageSize = $pageSize;
    }

    /**
     * 装载插件
     *
     * @param QueryList $queryList
     * @param mixed ...$opt
     */
    public static function install(QueryList $queryList, ...$opt)
    {
        $name = $opt[0] ?? 'baidu';
        $queryList->bind($name, function ($pageSize = 10) {
            return new Baidu($this, $pageSize);
        });
    }

    /**
     * 设置HTTP选项
     *
     * @param array $httpOpt
     * @return \QL\Ext\Baidu
     */
    public function setHttpOpt(array $httpOpt = [])
    {
        $this->httpOpt = $httpOpt;
        return $this;
    }

    /**
     * 设置搜索关键词
     *
     * @param string $keyword
     * @return \QL\Ext\Baidu
     */
    public function search(string $keyword)
    {
        $this->keyword = $keyword;
        return $this;
    }

    /**
     * 获取搜索结果
     *
     * @param int $page
     * @param boolean $realURL
     * @return Tightenco\Collect\Support\Collection
     */
    public function page(int $page = 1, bool $realURL = false)
    {
        return $this->query($page)->query()->getData(function ($item) use($realURL) {
            $realURL && $item['link'] = $this->getRealURL($item['link']);
            return $item;
        });
    }

    /**
     * 获取相关搜索
     *
     * @return Tightenco\Collect\Support\Collection
     */
    public function getRelatedSearches()
    {
        // 选择器
        $table = $this->query()->find('#rs>table');
        
        // 获取记录
        $rows = $table->find('tr')->map(function ($row) {
            return $row->find('th')->texts()->all();
        });
        
        return $rows->collapse();
    }

    /**
     * 获取搜索结果总条数
     *
     * @return integer
     */
    public function getCount()
    {
        $count = 0;
        $text = $this->query(1)->find('.nums')->text();
        if (preg_match('/[\d,]+/', $text, $arr)) {
            $count = str_replace(',', '', $arr[0]);
        }
        
        return (int)$count;
    }

    /**
     * 获取搜索结果总页数
     *
     * @return integer
     */
    public function getCountPage()
    {
        $count = $this->getCount();
        $countPage = ceil($count / $this->pageSize);
        
        return $countPage;
    }

    /**
     * 获取原始数据
     *
     * @param number $page
     * @return QL\QueryList
     */
    protected function query(int $page = 1)
    {
        $this->queryList->get(self::API, [
            'wd' => $this->keyword,
            'rn' => $this->pageSize,
            'pn' => $this->pageSize * ($page - 1)
        ], $this->httpOpt);
        
        return $this->queryList;
    }

    /**
     * 获取百度跳转的真正地址
     *
     * @param string $url
     * @return string
     */
    protected function getRealURL(string $url)
    {
        // 得到百度跳转的真正地址
        $header = get_headers($url, 1);
        if (strpos($header[0], '301') || strpos($header[0], '302')) {
            if (is_array($header['Location'])) {
                return $header['Location'][0];
            } else {
                return $header['Location'];
            }
        } else {
            return $url;
        }
    }
}
