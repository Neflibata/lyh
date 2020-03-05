<?php
/**
 * @Copyright 2019-2019 shuibo All Rights Reserved.
 *Support:http://www.shuibo.net
 * @Author:zfy
 * @Version 1.0 2019/10/11
 */
namespace plugins\file_manage\model;

use plugins\file_manage\services\ModelBaseModel;


class PluginFileModel extends ModelBaseModel
{
    protected $table="cmf_file";
    protected $pk = 'id';

    /**
     * 获取所有列表
     * @param $where 条件数据
     * @param string $order 排序
     * @return array
     */
    public static function getall($where,$order='id desc')
    {
        $model = new self;
        if(isset($where['filename'] ) && $where['filename'] !== '') $model = $model->where('filename','LIKE',"%$where[title]%");
        if(isset($where['parm'] ) &&   $where['parm']!=='') $model = $model->where('enable','eq',$where['enable']);
        if(isset($where['filetype'] ) &&   $where['filetype']!=='') $model = $model->where('filetype','eq',$where['filetype']);
        if(isset($where['start_time'] ) &&   $where['start_time']!=='') $model = $model->where('addtime','> time',$where['start_time']);
        if(isset($where['end_time'] ) &&   $where['end_time']!=='') $model = $model->where('addtime','< time',$where['end_time']);
        if($order)
        {
            $model = $model->order($order);
        }
        else
        {
            $model = $model->order('id asc');
        }
        return self::page($model,function($item){
//            $item['adtitle'] = self::gettidtotitle($item['adtid']);
        },$where);
    }

    /**
     * 获取一条数据
     * @param $where 条件数据
     * @param String $field 条件数据
     * @return array
     */
    public static function get_one($where,$field="*")
    {
        $model = new self;
        $model = $model->where($where)->field($field);
        return $model->find();
    }

    /**
     * 获取参数名称
     * @param $data 参数值
     * @return mixed
     */
    public static function add($data)
    {
        return PluginFileModel::set($data);
    }

    /**
     * 获取参数名称
     * @param $id 参数值
     * @return mixed
     */
    public static function gettidtotitle($id)
    {
        return PluginFileModel::where('id','eq',$id)->value('*');
    }

    /**
     * 根据文件后缀获取mime类型
     * @param string $ext 文件后缀
     * @return string mime类型
     */
    function get_mime_type($ext){
        static $mime_types = array (
            'apk' => 'application/vnd.android.package-archive',
            '3gp' => 'video/3gpp',
            'ai' => 'application/postscript',
            'aif' => 'audio/x-aiff',
            'aifc' => 'audio/x-aiff',
            'aiff' => 'audio/x-aiff',
            'asc' => 'text/plain',
            'atom' => 'application/atom+xml',
            'au' => 'audio/basic',
            'avi' => 'video/x-msvideo',
            'bcpio' => 'application/x-bcpio',
            'bin' => 'application/octet-stream',
            'bmp' => 'image/bmp',
            'cdf' => 'application/x-netcdf',
            'cgm' => 'image/cgm',
            'class' => 'application/octet-stream',
            'cpio' => 'application/x-cpio',
            'cpt' => 'application/mac-compactpro',
            'csh' => 'application/x-csh',
            'css' => 'text/css',
            'dcr' => 'application/x-director',
            'dif' => 'video/x-dv',
            'dir' => 'application/x-director',
            'djv' => 'image/vnd.djvu',
            'djvu' => 'image/vnd.djvu',
            'dll' => 'application/octet-stream',
            'dmg' => 'application/octet-stream',
            'dms' => 'application/octet-stream',
            'doc' => 'application/msword',
            'dtd' => 'application/xml-dtd',
            'dv' => 'video/x-dv',
            'dvi' => 'application/x-dvi',
            'dxr' => 'application/x-director',
            'eps' => 'application/postscript',
            'etx' => 'text/x-setext',
            'exe' => 'application/octet-stream',
            'ez' => 'application/andrew-inset',
            'flv' => 'video/x-flv',
            'gif' => 'image/gif',
            'gram' => 'application/srgs',
            'grxml' => 'application/srgs+xml',
            'gtar' => 'application/x-gtar',
            'gz' => 'application/x-gzip',
            'hdf' => 'application/x-hdf',
            'hqx' => 'application/mac-binhex40',
            'htm' => 'text/html',
            'html' => 'text/html',
            'ice' => 'x-conference/x-cooltalk',
            'ico' => 'image/x-icon',
            'ics' => 'text/calendar',
            'ief' => 'image/ief',
            'ifb' => 'text/calendar',
            'iges' => 'model/iges',
            'igs' => 'model/iges',
            'jnlp' => 'application/x-java-jnlp-file',
            'jp2' => 'image/jp2',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'js' => 'application/x-javascript',
            'kar' => 'audio/midi',
            'latex' => 'application/x-latex',
            'lha' => 'application/octet-stream',
            'lzh' => 'application/octet-stream',
            'm3u' => 'audio/x-mpegurl',
            'm4a' => 'audio/mp4a-latm',
            'm4p' => 'audio/mp4a-latm',
            'm4u' => 'video/vnd.mpegurl',
            'm4v' => 'video/x-m4v',
            'mac' => 'image/x-macpaint',
            'man' => 'application/x-troff-man',
            'mathml' => 'application/mathml+xml',
            'me' => 'application/x-troff-me',
            'mesh' => 'model/mesh',
            'mid' => 'audio/midi',
            'midi' => 'audio/midi',
            'mif' => 'application/vnd.mif',
            'mov' => 'video/quicktime',
            'movie' => 'video/x-sgi-movie',
            'mp2' => 'audio/mpeg',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'mpe' => 'video/mpeg',
            'mpeg' => 'video/mpeg',
            'mpg' => 'video/mpeg',
            'mpga' => 'audio/mpeg',
            'ms' => 'application/x-troff-ms',
            'msh' => 'model/mesh',
            'mxu' => 'video/vnd.mpegurl',
            'nc' => 'application/x-netcdf',
            'oda' => 'application/oda',
            'ogg' => 'application/ogg',
            'ogv' => 'video/ogv',
            'pbm' => 'image/x-portable-bitmap',
            'pct' => 'image/pict',
            'pdb' => 'chemical/x-pdb',
            'pdf' => 'application/pdf',
            'pgm' => 'image/x-portable-graymap',
            'pgn' => 'application/x-chess-pgn',
            'pic' => 'image/pict',
            'pict' => 'image/pict',
            'png' => 'image/png',
            'pnm' => 'image/x-portable-anymap',
            'pnt' => 'image/x-macpaint',
            'pntg' => 'image/x-macpaint',
            'ppm' => 'image/x-portable-pixmap',
            'ppt' => 'application/vnd.ms-powerpoint',
            'ps' => 'application/postscript',
            'qt' => 'video/quicktime',
            'qti' => 'image/x-quicktime',
            'qtif' => 'image/x-quicktime',
            'ra' => 'audio/x-pn-realaudio',
            'ram' => 'audio/x-pn-realaudio',
            'ras' => 'image/x-cmu-raster',
            'rdf' => 'application/rdf+xml',
            'rgb' => 'image/x-rgb',
            'rm' => 'application/vnd.rn-realmedia',
            'rmvb' => 'application/vnd.rn-realmedia',
            'roff' => 'application/x-troff',
            'rtf' => 'text/rtf',
            'rtx' => 'text/richtext',
            'sgm' => 'text/sgml',
            'sgml' => 'text/sgml',
            'sh' => 'application/x-sh',
            'shar' => 'application/x-shar',
            'silo' => 'model/mesh',
            'sit' => 'application/x-stuffit',
            'skd' => 'application/x-koan',
            'skm' => 'application/x-koan',
            'skp' => 'application/x-koan',
            'skt' => 'application/x-koan',
            'smi' => 'application/smil',
            'smil' => 'application/smil',
            'snd' => 'audio/basic',
            'so' => 'application/octet-stream',
            'spl' => 'application/x-futuresplash',
            'src' => 'application/x-wais-source',
            'sv4cpio' => 'application/x-sv4cpio',
            'sv4crc' => 'application/x-sv4crc',
            'svg' => 'image/svg+xml',
            'swf' => 'application/x-shockwave-flash',
            't' => 'application/x-troff',
            'tar' => 'application/x-tar',
            'tcl' => 'application/x-tcl',
            'tex' => 'application/x-tex',
            'texi' => 'application/x-texinfo',
            'texinfo' => 'application/x-texinfo',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff',
            'tr' => 'application/x-troff',
            'tsv' => 'text/tab-separated-values',
            'txt' => 'text/plain',
            'ustar' => 'application/x-ustar',
            'vcd' => 'application/x-cdlink',
            'vrml' => 'model/vrml',
            'vxml' => 'application/voicexml+xml',
            'wav' => 'audio/x-wav',
            'wbmp' => 'image/vnd.wap.wbmp',
            'wbxml' => 'application/vnd.wap.wbxml',
            'webm' => 'video/webm',
            'wml' => 'text/vnd.wap.wml',
            'wmlc' => 'application/vnd.wap.wmlc',
            'wmls' => 'text/vnd.wap.wmlscript',
            'wmlsc' => 'application/vnd.wap.wmlscriptc',
            'wmv' => 'video/x-ms-wmv',
            'wrl' => 'model/vrml',
            'xbm' => 'image/x-xbitmap',
            'xht' => 'application/xhtml+xml',
            'xhtml' => 'application/xhtml+xml',
            'xls' => 'application/vnd.ms-excel',
            'xml' => 'application/xml',
            'xpm' => 'image/x-xpixmap',
            'xsl' => 'application/xml',
            'xslt' => 'application/xslt+xml',
            'xul' => 'application/vnd.mozilla.xul+xml',
            'xwd' => 'image/x-xwindowdump',
            'xyz' => 'chemical/x-xyz',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        );
        return isset($mime_types[$ext]) ? $mime_types[$ext] : '';
    }

}
