<?php
	/**
	 * @class
	 * @author zero (zero@nzeo.com)
	 * @brief 최근 팝업창을 출력하는 위젯
	 * @version 0.1
	 **/

	class mh_appoint_popup extends WidgetHandler {

		/**
		 * @brief 위젯의 실행 부분
		 *
		 * ./widgets/위젯/conf/info.xml 에 선언한 extra_vars를 args로 받는다
		 * 결과를 만든후 print가 아니라 return 해주어야 한다
		 **/
			
		function proc($args) {

			$args->subject_cut_size = isset($args->subject_cut_size) ? (int)$args->subject_cut_size : 0;
			$args->content_cut_size = isset($args->content_cut_size) ? (int)$args->content_cut_size : 100;
			$args->board_top = isset($args->board_top) ? (int)$args->board_top : 10;
			$args->board_left = isset($args->board_left) ? (int)$args->board_left : 50;
			$args->popup_width = isset($args->popup_width) ? (int)$args->popup_width : 460;
			$args->popup_height = isset($args->popup_height) ? (int)$args->popup_height : '';
			$args->close_b = isset($args->close_b) ? (int)$args->close_b : 5;
			$args->close_left = isset($args->close_left) ? (int)$args->close_left : 10;
			$args->closeday = isset($args->closeday) ? (int)$args->closeday : 1;
			$args->board_line_width = isset($args->board_line_width) ? (int)$args->board_line_width : 1;
			$args->aboard_line_color = isset($args->aboard_line_color) ? $args->aboard_line_color : 'transparent';
			$args->title_font_color = isset($args->title_font_color) ? $args->title_font_color : '#666';
			$args->titles_font_color = isset($args->titles_font_color) ? $args->titles_font_color : '#555';
			$args->background_color = isset($args->background_color) ? $args->background_color : 'transparent';
			$args->summary_font_color = isset($args->summary_font_color) ? $args->summary_font_color : '#888';
			$args->close_bcolor = isset($args->close_bcolor) ? $args->close_bcolor : '#000';
			$args->close_color = isset($args->close_color) ? $args->close_color : '#fff';
			$args->group = isset($args->group) ? $args->group : '';
			$args->group1 = isset($args->group1) ? $args->group1 : '';
			$args->direct_sub_color = isset($args->direct_sub_color) ? $args->direct_sub_color : '#555';
			$args->popup_no = isset($args->popup_no) ? $args->popup_no : 'a';
			$args->top_close_t = isset($args->top_close_t) ? (int)$args->top_close_t : 7;
			$args->top_close_r = isset($args->top_close_r) ? (int)$args->top_close_r : 10;

			// 최근 글 표시 시간
			$obj->list_count = $list_count;
			if($obj->sort_index == 'list_order') $obj->avoid_notice = -2100000000;

			/* XML로 넘기기 위해 obj로 선언 */
			// 대상 모듈 (mid_list는 기존 위젯의 호환을 위해서 처리하는 루틴을 유지. module_srl로 위젯에서 변경)
			$oModuleModel = &getModel('module');
			if($args->mid_list) {
				$mid_list = explode(",",$args->mid_list);
				if(count($mid_list)) {
					$module_srl = $oModuleModel->getModuleSrlByMid($mid_list);
				} else {
					$site_module_info = Context::get('site_module_info');
					if($site_module_info) {
						$margs->site_srl = $site_module_info->site_srl;
						$oModuleModel = &getModel('module');
						$output = $oModuleModel->getMidList($margs);
						if(count($output)) $mid_list = array_keys($output);
						$module_srl = $oModuleModel->getModuleSrlByMid($mid_list);
					}
				}
			} else $module_srl = explode(',',$args->module_srls);

			// DocumentModel::getDocumentList()를 이용하기 위한 변수 정리

			$obj->list_count = $list_count;
			$obj->document_srl = $args->document_srl;
			$output = executeQueryArray('document.getDocument', $obj);

			// document 모듈의 model 객체를 받아서 결과를 객체화 시킴
			$oDocumentModel = &getModel('document');

			// 오류가 생기면 그냥 무시
			if(!$output->toBool()) return;

			// 결과가 있으면 각 문서 객체화를 시킴
			if(count($output->data)) {
				foreach($output->data as $key => $attribute) {
					$document_srl = $attribute->document_srl;

					$oDocument = null;
					$oDocument = new documentItem();
					$oDocument->setAttribute($attribute);

					$document_list[$key] = $oDocument;

					/* 출력되는 모듈의 번호를 구함 */
					$document_modulies[$attribute->module_srl] = $attribute->module_srl;
				}
			} else {
				$document_list = array();
			}

			//회원그룹 권한 확인
			if(Context::get('is_logged'))
			{
				$logged_info = Context::get('logged_info');

				if(!$args->group_srls)
				{
					$args->permission = true;
				}
				else
				{
					$args->permission = false;
					$group_array = explode (',', $args->group_srls); 
					foreach($logged_info->group_list as $key => $value)
					{
						if(in_array($key, $group_array))
						{
							$args->permission = true;
							break;
						}
					}
				}
			}

			// 템플릿 파일에서 사용할 변수들을 세팅
			$args->document_list = $document_list;

			if(is_array($module_srl)) $args->module_srl = implode(',',$module_srl);
			else $args->module_srl = $module_srl;
			Context::set('widget_info', $args);

			// 템플릿의 스킨 경로를 지정 (skin, colorset에 따른 값을 설정)
			$tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
			Context::set('colorset', $args->colorset);

			// 템플릿 파일을 지정
			$tpl_file = 'list';

			// 템플릿 컴파일
			$oTemplate = &TemplateHandler::getInstance();
			$output = $oTemplate->compile($tpl_path, $tpl_file);
			return $output;
		}
	}
?>