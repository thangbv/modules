<?php

/**
 * @Project NUKEVIET 3.x
 * @Author VINADES.,JSC (contact@vinades.vn)
 * @Copyright (C) 2013 VINADES.,JSC. All rights reserved
 * @Createdate Wed, 10 Jul 2013 09:15:32 GMT
 */

if( ! defined( 'NV_IS_MOD_MAKE_THEME' ) )
	die( 'Stop!!!' );

$page_title = $mod_title = $module_info['custom_title'];
$key_words = $module_info['keywords'];

function nv_file_config( $data )
{
	global $global_config;

	$position = "";
	foreach( $data['position_tag'] as $key => $tag )
	{
		$tag = str_replace( '-', '_', strtoupper( change_alias( $tag ) ) );
		$name_en = strip_punctuation( $data['position_name'][$key] );
		$name_vi = strip_punctuation( $data['position_name_vi'][$key] );
		if( ! empty( $tag ) AND ! empty( $name_en ) )
		{
			if( empty( $name_vi ) )
			{
				$name_vi = $name_en;
			}
			$position .= "\t\t<position>\n\t\t\t<tag>[" . $tag . "]</tag>\n\t\t\t<name>" . $name_en . "</name>\n\t\t\t<name_vi>" . $name_vi . "</name_vi>\n\t\t</position>\n";
		}
	}
	$res = "<?xml version='1.0'?>
<theme>
	<info>
		<name>" . $data['info_name'] . "</name>
		<author>" . $data['info_author'] . "</author>
		<website>" . $data['info_website'] . "</website>
		<description>" . $data['info_description'] . "</description>
		<thumbnail>" . $data['theme'] . ".jpg</thumbnail>
	</info>
	<layoutdefault>body</layoutdefault>
	<positions>\n" . $position . "\t</positions>
	<setlayout>
		<layout>
			<name>body</name>
			<funcs>about:main</funcs>
			<funcs>rss:main</funcs>
			<funcs>statistics:main,allreferers,allcountries,allbrowsers,allos,allbots,referer</funcs>
		</layout>
	</setlayout>	
</theme>";
	return $res;
}

function nv_file_layout_body( )
{
	$res = '<!-- BEGIN: main -->
{FILE "header.tpl"}
{MODULE_CONTENT}
{FILE "footer.tpl"}
<!-- END: main -->';
	return $res;
}

function nv_file_layout_html( $dir_theme )
{
	$html = file_get_contents( $dir_theme . '/index.html' );
	//Xóa bỏ jquery.min.js
	if( preg_match_all( "/<script[^>]+src\s*=([^>]+)>[\s\r\n\t]*<\/script>/is", $html, $m ) )
	{
		foreach( $m[1] as $key => $value )
		{
			if( strpos( $value, '/jquery.min.js' ) OR preg_match( '/\/jquery\-[0-9\.]+\.min\.js/', $value ) )
			{
				$value = str_replace( '"', '', $value );
				$value = str_replace( "'", '', $value );
				if( is_file( $dir_theme . '/' . $value ) )
				{
					unlink( $dir_theme . '/' . $value );
				}
				$html = str_replace( $m[0][$key], '', $html );
			}
		}
	}

	$html = str_replace( '="css/', '="{NV_BASE_SITEURL}themes/{TEMPLATE}/css/', $html );
	$html = str_replace( '="/css/', '="{NV_BASE_SITEURL}themes/{TEMPLATE}/css/', $html );
	$html = str_replace( '="images/', '="{NV_BASE_SITEURL}themes/{TEMPLATE}/images/', $html );
	$html = str_replace( '="/images/', '="{NV_BASE_SITEURL}themes/{TEMPLATE}/images/', $html );
	$html = str_replace( '="js/', '="{NV_BASE_SITEURL}themes/{TEMPLATE}/js/', $html );
	$html = str_replace( '="/js/', '="{NV_BASE_SITEURL}themes/{TEMPLATE}/js/', $html );
	$html = str_replace( '="uploads/', '="{NV_BASE_SITEURL}themes/{TEMPLATE}/uploads/', $html );
	$html = str_replace( '="/uploads/', '="{NV_BASE_SITEURL}themes/{TEMPLATE}/uploads/', $html );
	$html = preg_replace( '/<meta[^>]+>/', '', $html );
	$html = preg_replace( '/<title>[^<]+<\/title>/', '', $html );

	$html = preg_replace( '/<head>/i', "<head>\n\t{THEME_PAGE_TITLE}\n\t{THEME_META_TAGS}\n", $html, 1 );
	$html = preg_replace( '/<\/head>/i', "\n\t{THEME_CSS}\n\t{THEME_SITE_RSS}\n\t{THEME_SITE_JS}\n</head>", $html, 1 );

	//Xóa các dòng trống có tab, hoặc có nhiều hơn 1 dòng trống
	$html = trim( preg_replace( '/\n([\t\n]+)\n/', "\n\n", $html ) );

	mkdir( $dir_theme . '/html' );
	if( preg_match_all( '/<!--\sbegin\sblock\:([^\>]+)\s-->/', $html, $variable ) )
	{
		foreach( $variable[1] as $tag_i )
		{
			$a1 = strpos( $html, '<!-- begin block:' . $tag_i . ' -->' );
			$a2 = strpos( $html, '<!-- end block:' . $tag_i . ' -->' );
			$html_block_i = substr( $html, $a1, $a2 + strlen( '<!-- end block:' . $tag_i . ' -->' ) - $a1 );
			$tag_i = str_replace( '-', '_', strtoupper( change_alias( $tag_i ) ) );
			$html = str_replace( $html_block_i, '[' . $tag_i . ']', $html );
			file_put_contents( $dir_theme . '/html/block_' . strtolower( $tag_i ) . '.tpl', $html_block_i );
		}
	}

	$a1 = strpos( $html, '<!-- begin nv_content -->' );
	$a2 = strpos( $html, '<!-- end nv_content -->' );
	$html_nv_content = substr( $html, $a1, $a2 + strlen( '<!-- end nv_content -->' ) - $a1 );
	$html = str_replace( $html_nv_content, '{MODULE_CONTENT}', $html );
	file_put_contents( $dir_theme . '/html/content.tpl', $html_nv_content );

	$a1 = strpos( $html, '<!-- begin nv_body -->' );
	$a2 = strpos( $html, '<!-- end nv_body -->' );
	$html_header = substr( $html, 0, $a1 );
	$html_footer = substr( $html, $a2 + strlen( '<!-- end nv_body -->' ) );
	$a1 = $a1 + strlen( '<!-- begin nv_body -->' );
	$html = substr( $html, $a1, $a2 - $a1 );

	file_put_contents( $dir_theme . '/layout/header.tpl', $html_header );
	file_put_contents( $dir_theme . '/layout/footer.tpl', $html_footer );
	$html = "<!-- BEGIN: main -->\n{FILE \"header.tpl\"}\n" . $html . "\n{FILE \"footer.tpl\"}\n<!-- END: main -->";

	//Xóa các dòng trống có tab, hoặc có nhiều hơn 1 dòng trống
	$html = trim( preg_replace( '/\n([\t\n]+)\n/', "\n\n", $html ) );

	file_put_contents( $dir_theme . '/layout/layout.body.tpl', $html );
}

$step = $nv_Request->get_int( 'step', 'get,post' );

if( $step == 2 )
{
	$theme = $nv_Request->get_title( 'theme_upload', 'session' );
	if( ! empty( $theme ) AND is_dir( NV_ROOTDIR . '/' . NV_TEMP_DIR . '/' . NV_TEMPNAM_PREFIX . md5( $global_config['sitekey'] . session_id( ) ) . '/' . $theme ) )
	{
		$data = array( 'theme' => $theme );
		$data['theme'] = change_alias( $nv_Request->get_title( 'theme', 'post', $theme ) );
		$data['info_name'] = $nv_Request->get_title( 'info_name', 'post', 'Theme ' . $theme, 1 );

		$data['info_author'] = $nv_Request->get_title( 'info_author', 'post', 'VinaDes.,Jsc', 1 );
		$data['info_website'] = $nv_Request->get_title( 'info_website', 'post', 'http://vinades.vn' );
		if( ! nv_is_url( $data['info_website'] ) )
		{
			$data['info_website'] = '';
		}
		$data['info_description'] = $nv_Request->get_title( 'info_description', 'post', 'Theme for NukeViet 3', 1 );
		$data['version'] = $nv_Request->get_title( 'version', 'post', 3.5 );
		$data['layout'] = $nv_Request->get_typed_array( 'layout', 'post', null, 'int' );
		$data['layoutdefault'] = $nv_Request->get_title( 'layoutdefault', 'post', 'body', 1 );

		$data['position_tag'] = $nv_Request->get_typed_array( 'position_tag', 'post', null, 'int' );
		$data['position_name'] = $nv_Request->get_typed_array( 'position_name', 'post', null, 'int' );
		$data['position_name_vi'] = $nv_Request->get_typed_array( 'position_name_vi', 'post', null, 'int' );

		$source_theme = NV_ROOTDIR . '/modules/make-theme/theme/modern_' . $data['version'];
		if( $nv_Request->isset_request( 'submit', 'post' ) AND is_dir( $source_theme ) )
		{
			$dest_theme = NV_ROOTDIR . '/' . NV_TEMP_DIR . '/' . NV_TEMPNAM_PREFIX . md5( $global_config['sitekey'] . session_id( ) ) . '/' . $theme;
			file_put_contents( $dest_theme . '/config.ini', nv_file_config( $data ) );
			copy( $source_theme . '/favicon.ico', $dest_theme . '/favicon.ico' );
			copy( $source_theme . '/theme.php', $dest_theme . '/theme.php' );

			mkdir( $dest_theme . '/blocks' );
			file_put_contents( $dest_theme . '/blocks/index.html', '' );
			file_put_contents( $dest_theme . '/blocks/.htaccess', 'deny from all' );
			copy( $source_theme . '/blocks/global.counter.tpl', $dest_theme . '/blocks/global.counter.tpl' );
			copy( $source_theme . '/blocks/global.rss.tpl', $dest_theme . '/blocks/global.rss.tpl' );

			if( ! is_dir( $dest_theme . '/css' ) )
			{
				mkdir( $dest_theme . '/css' );
				file_put_contents( $dest_theme . '/css/index.html', '' );
			}
			$copy_file_css = array_map( 'trim', explode( ',', 'admin.css, icons.css, ie6.css, sitemap.xsl, sitemapindex.xsl, tab_info.css' ) );
			foreach( $copy_file_css as $file_i )
			{
				copy( $source_theme . '/css/' . $file_i, $dest_theme . '/css/' . $file_i );
			}

			if( ! is_dir( $dest_theme . '/images' ) )
			{
				mkdir( $dest_theme . '/images' );
				file_put_contents( $dest_theme . '/images/index.html', '' );
			}
			$copy_file_images = array_map( 'trim', explode( ',', 'admin, arrows, icons' ) );
			foreach( $copy_file_images as $dir_i )
			{
				mkdir( $dest_theme . '/images/' . $dir_i );
				$array_file_i = nv_scandir( $source_theme . '/images/' . $dir_i, "/([a-zA-Z0-9\.\-\_\\s\(\)]+)\.([a-zA-Z0-9]+)$/" );
				foreach( $array_file_i as $file_i )
				{
					copy( $source_theme . '/images/' . $dir_i . '/' . $file_i, $dest_theme . '/images/' . $dir_i . '/' . $file_i );
				}
			}

			mkdir( $dest_theme . '/layout' );
			file_put_contents( $dest_theme . '/layout/index.html', '' );
			file_put_contents( $dest_theme . '/layout/.htaccess', 'deny from all' );
			file_put_contents( $dest_theme . '/layout/header.tpl', '' );
			file_put_contents( $dest_theme . '/layout/footer.tpl', '' );
			copy( $source_theme . '/layout/block.default.tpl', $dest_theme . '/layout/block.default.tpl' );
			copy( $source_theme . '/layout/block.no_title.tpl', $dest_theme . '/layout/block.no_title.tpl' );
			nv_file_layout_html( $dest_theme );

			mkdir( $dest_theme . '/modules' );
			file_put_contents( $dest_theme . '/modules/index.html', '' );
			file_put_contents( $dest_theme . '/modules/.htaccess', 'deny from all' );

			mkdir( $dest_theme . '/language' );
			file_put_contents( $dest_theme . '/language/index.html', '' );
			file_put_contents( $dest_theme . '/language/.htaccess', 'deny from all' );

			mkdir( $dest_theme . '/system' );
			file_put_contents( $dest_theme . '/system/index.html', '' );
			file_put_contents( $dest_theme . '/system/.htaccess', 'deny from all' );
			copy( $source_theme . '/system/error_info.tpl', $dest_theme . '/system/error_info.tpl' );
			copy( $source_theme . '/system/flood_blocker.tpl', $dest_theme . '/system/flood_blocker.tpl' );
			copy( $source_theme . '/system/info_die.tpl', $dest_theme . '/system/info_die.tpl' );

			if( is_file( $dest_theme . '/view.jpg' ) )
			{
				$imageinfo = @getimagesize( $dest_theme . '/view.jpg' );
				if( $imageinfo[0] > 400 )
				{
					// Resize về kích thước 300x145
					require_once (NV_ROOTDIR . "/includes/class/image.class.php");
					$createImage = new image( $dest_theme . '/view.jpg' );
					$createImage->resizeXY( 300 );
					$createImage->cropFromLeft( 0, 0, 300, 145 );
					$createImage->save( $dest_theme, $data['theme'] . '.jpg', 100 );
					$createImage->close( );

					unlink( $dest_theme . '/view.jpg' );
				}
				else
				{
					rename( $dest_theme . '/view.jpg', $dest_theme . '/' . $data['theme'] . '.jpg' );
				}
			}
			else
			{
				// Tạo ảnh minh họa rỗng
				// Create a blank image and add some text
				$im = imagecreatetruecolor( 300, 145 );
				$bgc = imagecolorallocate( $im, 255, 255, 255 );
				imagefilledrectangle( $im, 0, 0, 300, 145, $bgc );
				$text_color = imagecolorallocate( $im, 0, 0, 0 );
				imagestring( $im, 1, 5, 5, 'Theme ' . $data['theme'], $text_color );
				// Save the image as 'simpletext.jpg'
				imagejpeg( $im, $dest_theme . '/' . $data['theme'] . '.jpg', 100 );
				// Free up memory
				imagedestroy( $im );
			}

			file_put_contents( $dest_theme . '/css/index.html', '' );
			file_put_contents( $dest_theme . '/fonts/index.html', '' );
			file_put_contents( $dest_theme . '/images/index.html', '' );
			file_put_contents( $dest_theme . '/js/index.html', '' );
			file_put_contents( $dest_theme . '/uploads/index.html', '' );
			file_put_contents( $dest_theme . '/index.html', '' );

			if( $theme != $data['theme'] )
			{
				rename( $dest_theme, NV_ROOTDIR . '/' . NV_TEMP_DIR . '/' . NV_TEMPNAM_PREFIX . md5( $global_config['sitekey'] . session_id( ) ) . '/' . $data['theme'] );
				$dest_theme = NV_ROOTDIR . '/' . NV_TEMP_DIR . '/' . NV_TEMPNAM_PREFIX . md5( $global_config['sitekey'] . session_id( ) ) . '/' . $data['theme'];
			}
			$file_src = NV_ROOTDIR . '/' . NV_TEMP_DIR . '/' . NV_TEMPNAM_PREFIX . 'theme_' . $data['theme'] . '_' . md5( nv_genpass( 10 ) . session_id( ) ) . '.zip';
			require_once NV_ROOTDIR . '/includes/class/pclzip.class.php';

			$zip = new PclZip( $file_src );
			$zip->create( $dest_theme, PCLZIP_OPT_REMOVE_PATH, NV_ROOTDIR . '/' . NV_TEMP_DIR . '/' . NV_TEMPNAM_PREFIX . md5( $global_config['sitekey'] . session_id( ) ) );

			//Xoa file tam
			nv_deletefile( NV_ROOTDIR . '/' . NV_TEMP_DIR . '/' . NV_TEMPNAM_PREFIX . md5( $global_config['sitekey'] . session_id( ) ), 1 );

			//Download file
			require_once (NV_ROOTDIR . '/includes/class/download.class.php');
			$download = new download( $file_src, NV_ROOTDIR . "/" . NV_TEMP_DIR, 'nv3_theme_' . $data['theme'] . '.zip' );
			$download->download_file( );
			exit( );
		}
		else
		{
			$html = file_get_contents( NV_ROOTDIR . '/' . NV_TEMP_DIR . '/' . NV_TEMPNAM_PREFIX . md5( $global_config['sitekey'] . session_id( ) ) . '/' . $theme . '/index.html' );

			$xtpl = new XTemplate( "info.tpl", NV_ROOTDIR . "/themes/" . $module_info['template'] . "/modules/" . $module_file );
			$xtpl->assign( 'LANG', $lang_module );
			$xtpl->assign( 'NV_BASE_SITEURL', NV_BASE_SITEURL );
			$xtpl->assign( 'NV_NAME_VARIABLE', NV_NAME_VARIABLE );
			$xtpl->assign( 'NV_OP_VARIABLE', NV_OP_VARIABLE );
			$xtpl->assign( 'MODULE_NAME', $module_name );
			$xtpl->assign( 'OP', $op );
			$xtpl->assign( 'DATA', $data );
			$id = 0;
			if( preg_match_all( '/<!--\sbegin\sblock\:([^\>]+)\s-->/', $html, $variable ) )
			{
				foreach( $variable[1] as $tag_name )
				{
					$tag_i = str_replace( '-', '_', strtoupper( change_alias( $tag_name ) ) );
					$position = array(
						'id' => ++$id,
						'class' => ($id % 2 == 0) ? ' class="second"' : '',
						'tag' => $tag_i,
						'name' => $tag_name,
						'name_vi' => $tag_name
					);
					$xtpl->assign( 'POSITION', $position );
					$xtpl->parse( 'main.loop' );
				}
			}
			$xtpl->assign( 'ITEMS_POSITIONS', $id );

			$xtpl->parse( 'main' );
			$contents = $xtpl->text( 'main' );
		}
	}
	else
	{
		$redirect = NV_BASE_SITEURL . "index.php?" . NV_NAME_VARIABLE . "=" . $module_name;
		$nv_Request->set_Session( 'theme_upload', $theme );
		nv_info_die( $global_config['site_description'], $lang_global['site_info'], $lang_module['upload_no_exit'] . " \n <meta http-equiv=\"refresh\" content=\"3;URL=" . $redirect . "\" />" );
	}
}
else
{
	if( $nv_Request->isset_request( 'submit', 'post' ) )
	{
		if( is_uploaded_file( $_FILES['zipfile']['tmp_name'] ) )
		{
			preg_match( "/^(.*)\.[a-zA-Z0-9]+$/", $_FILES['zipfile']['name'], $f );
			$theme = change_alias( $f[1] );

			$filename = NV_ROOTDIR . '/' . NV_TEMP_DIR . '/' . NV_TEMPNAM_PREFIX . 'theme_' . $theme . '-' . md5( $global_config['sitekey'] . session_id( ) ) . '.zip';
			if( move_uploaded_file( $_FILES['zipfile']['tmp_name'], $filename ) )
			{
				require_once NV_ROOTDIR . '/includes/class/pclzip.class.php';
				$zip = new PclZip( $filename );
				$status = $zip->properties( );

				$redirect = NV_BASE_SITEURL . "index.php?" . NV_NAME_VARIABLE . "=" . $module_name;
				if( $status['status'] == 'ok' )
				{
					$list = $zip->listContent( );
					$error = array( );
					$filen_index_html = 0;
					// Cần viết chương trình check xem có file php không
					foreach( $list as $_arf )
					{
						if( preg_match( "/^(.*)\.php$/i", $_arf['filename'], $f ) )
						{
							$error[] = $lang_module['upload_error_php_file'];
						}
						elseif( $_arf['filename'] == 'index.html' )
						{
							$filen_index_html = 1;
						}
					}
					if( empty( $filen_index_html ) )
					{
						$error[] = $lang_module['upload_error_index_html'];
					}
					if( empty( $error ) )
					{
						$temp_extract_dir = NV_TEMP_DIR . '/' . NV_TEMPNAM_PREFIX . md5( $global_config['sitekey'] . session_id( ) );
						if( NV_ROOTDIR . '/' . $temp_extract_dir )
						{
							nv_deletefile( NV_ROOTDIR . '/' . $temp_extract_dir, true );
						}
						$extract = $zip->extract( PCLZIP_OPT_PATH, NV_ROOTDIR . '/' . $temp_extract_dir . '/' . $theme );
					}
					else
					{
						$lang_module['upload_ok'] = implode( '<br />', $error );
					}
					nv_deletefile( $filename );

					$redirect = NV_BASE_SITEURL . "index.php?" . NV_NAME_VARIABLE . "=" . $module_name . "&step=2";
					$nv_Request->set_Session( 'theme_upload', $theme );
				}
				else
				{
					$lang_module['upload_ok'] = $lang_module['upload_error_zip_file'];
				}
				nv_info_die( $global_config['site_description'], $lang_global['site_info'], $lang_module['upload_ok'] . " \n <meta http-equiv=\"refresh\" content=\"1;URL=" . $redirect . "\" />" );
			}
		}
	}

	$xtpl = new XTemplate( $op . ".tpl", NV_ROOTDIR . "/themes/" . $module_info['template'] . "/modules/" . $module_file );
	$xtpl->assign( 'LANG', $lang_module );
	$xtpl->assign( 'NV_BASE_SITEURL', NV_BASE_SITEURL );
	$xtpl->assign( 'NV_NAME_VARIABLE', NV_NAME_VARIABLE );
	$xtpl->assign( 'NV_OP_VARIABLE', NV_OP_VARIABLE );
	$xtpl->assign( 'MODULE_NAME', $module_name );
	$xtpl->assign( 'OP', $op );

	$xtpl->parse( 'main' );
	$contents = $xtpl->text( 'main' );
}

include (NV_ROOTDIR . "/includes/header.php");
echo nv_site_theme( $contents );
include (NV_ROOTDIR . "/includes/footer.php");
?>