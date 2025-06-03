<?php
/**
 * @class
 * @author zero (zero@nzeo.com)
 * @brief 최근 팝업창을 출력하는 위젯
 * @version 0.2
 **/

class mh_appoint_popup extends WidgetHandler {

	/**
	 * @brief 위젯의 실행 부분
	 *
	 * ./widgets/위젯/conf/info.xml 에 선언한 extra_vars를 args로 받는다
	 * 결과를 만든후 print가 아니라 return 해주어야 한다
	 **/
	public function proc(object $args): string {
		// 기본값 설정
		$args->subject_cut_size = $args->subject_cut_size ?? 0;
		$args->content_cut_size = $args->content_cut_size ?? 100;
		$args->board_top = $args->board_top ?? 10;
		$args->board_left = $args->board_left ?? 50;
		$args->popup_width = $args->popup_width ?? 460;
		$args->popup_height = $args->popup_height ?? '';
		$args->close_b = $args->close_b ?? 5;
		$args->close_left = $args->close_left ?? 10;
		$args->closeday = $args->closeday ?? 1;
		$args->board_line_width = $args->board_line_width ?? 1;
		$args->aboard_line_color = $args->aboard_line_color ?? 'transparent';
		$args->title_font_color = $args->title_font_color ?? '#666';
		$args->titles_font_color = $args->titles_font_color ?? '#555';
		$args->background_color = $args->background_color ?? 'transparent';
		$args->summary_font_color = $args->summary_font_color ?? '#888';
		$args->close_bcolor = $args->close_bcolor ?? '#000';
		$args->close_color = $args->close_color ?? '#fff';
		$args->group = $args->group ?? '';
		$args->group1 = $args->group1 ?? '';
		$args->direct_sub_color = $args->direct_sub_color ?? '#555';
		$args->popup_no = $args->popup_no ?? 'a';
		$args->top_close_t = $args->top_close_t ?? 7;
		$args->top_close_r = $args->top_close_r ?? 10;
		$args->popup_time = $args->popup_time  ?? 10;

		// 모듈 모델 인스턴스 생성
		$oModuleModel = getModel('module');

		// mid_list 처리
		$module_srl = [];
		if (!empty($args->mid_list)) {
			$mid_list = array_filter(explode(",", $args->mid_list));
			if (!empty($mid_list)) {
				$module_srl = $oModuleModel->getModuleSrlByMid($mid_list);
			} else {
				$site_module_info = Context::get('site_module_info');
				if ($site_module_info) {
					$margs = new stdClass();
					$margs->site_srl = $site_module_info->site_srl;
					$output = $oModuleModel->getMidList($margs);
					if (!empty($output)) {
						$mid_list = array_keys($output);
						$module_srl = $oModuleModel->getModuleSrlByMid($mid_list);
					}
				}
			}
		} else {
			$module_srl = explode(',', $args->module_srls ?? '');
		}

		// 문서 조회
		$obj = new stdClass();
		$obj->list_count = $list_count;
		$obj->document_srl = $args->document_srl ?? null;
		if(isset($obj->sort_index) && $obj->sort_index === 'list_order') {
			$obj->avoid_notice = -2100000000;
		}

		$output = executeQueryArray('document.getDocument', $obj);

		// 문서 모델 인스턴스 생성
		$oDocumentModel = getModel('document');

		// 오류 체크
		if (!$output || !$output->toBool()) {
			return '';
		}

		// 문서 목록 처리
		$document_list = [];
		$document_modulies = [];
		
		if (!empty($output->data)) {
			foreach ($output->data as $key => $attribute) {
				$document_srl = $attribute->document_srl;
				$oDocument = new documentItem();
				$oDocument->setAttribute($attribute);
				$document_list[$key] = $oDocument;
				$document_modulies[$attribute->module_srl] = $attribute->module_srl;
			}
		}

		// 회원 그룹 권한 확인
		$args->permission = true;
		if (Context::get('is_logged')) {
			$logged_info = Context::get('logged_info');
			if (!empty($args->group_srls)) {
				$args->permission = false;
				$group_array = array_filter(explode(',', $args->group_srls));
				foreach ($logged_info->group_list as $key => $value) {
					if (in_array($key, $group_array)) {
						$args->permission = true;
						break;
					}
				}
			}
		}

		// 템플릿 변수 설정
		$args->document_list = $document_list;
		$args->module_srl = is_array($module_srl) ? implode(',', $module_srl) : $module_srl;
		Context::set('widget_info', $args);

		// 템플릿 경로 설정
		$tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
		Context::set('colorset', $args->colorset);

		// 템플릿 컴파일
		$oTemplate = TemplateHandler::getInstance();
		return $oTemplate->compile($tpl_path, 'list');
	}
}