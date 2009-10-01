<?php
Class MyActionView{
	function __get($strKey){
		$parts = array();
		if(strpos($strKey,',') !== false){
			$parts = explode(',',$strKey);
			$strKey = array_shift($parts);
		}
		if(substr($strKey,-3) == '_id'){
			$model = substr($strKey,0,-3);
			if(MyActiveRecord::TableExists($model)){
				if($object = $this->objMar->find_parent($model)){
					$columns = array_keys($this->objMar->Columns($model));
					array_shift($columns); //get rid of id
					$key = array_shift($columns);
					return $object->$key; //return first non-id column as human-readable string
				}
			}else{
				return false;
			}
		}elseif(method_exists( $this, $strKey ) ){
			return call_user_func_array( array($this, $strKey), $parts );
		}elseif(method_exists($this->objMar, $strKey)){
			return call_user_func_array( array($this->objMar, $strKey), $parts );
		}else{
			if (isset($this->$strKey)) return $this->$strKey;
			if (isset($this->objMar->$strKey)) return $this->objMar->$strKey;
			return false;
		}
	}

	function link_for($strVerb='',$strKey='',$strLinkType='full',$strLinkText=''){
		if(empty($strVerb)) $strVerb = 'view';
		if(empty($strKey)) $strKey = reset(array_keys($this->_public));
		if(empty($strLinkText)) $strLinkText = $this->$strKey;
		$arrLinkOptions = array(
			'model=' . get_class($this->objMar),
			'verb=' . $strVerb,
			'id=' . $this->objMar->id
			);
		$link = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] . '?' . implode('&',$arrLinkOptions);
		if($strLinkType != 'full') return $link;
		return '<a href="' . $link . '">' . $strLinkText . '</a>';
	}
	
	function MyActionView(&$objMar){
		$this->objMar = $objMar;
		$this->_columns = $objMar->Columns(get_class($objMar));
		$this->_public = array();
		foreach($this->_columns as $col){
			$k = $col['Field'];
			if($k && $k != 'id' && substr($k,0,1) != '_'){
				$this->_public[$k] = $col;
			}
		}
	}
	
	function picker($strKey,$arrOptions,$boolUseKeyAsValue=false,$boolCombo = false){
		$combo = ($boolCombo) ? ' class="combo"' : '';
		$out = '<select size="1" name="' . $strKey . '" id="' . $strKey . '"' . $combo . '>';
		$out .= '<option value="" label=""></option>';
		foreach($arrOptions as $k=>$o) {
			if($boolUseKeyAsValue){
				$out .= '<option label="' . $o . '" value="' . $k . '"';
				$out .= ($this->objMar->id > 0 && $k == $this->objMar->$strKey) ? ' selected="selected"' : '';
			}else{
				$out .= '<option label="' . $o . '" value="' . $o . '"';
				$out .= ($this->objMar->id > 0 && $o == $this->objMar->$strKey && '' !== $this->objMar->$strKey) ? ' selected="selected"' : '';
			}
			$out .= '>' . $o . '</option>';
		}
		return $out . '</select>';
	}
	
	function h($strKey){
		return $this->objMar->h($strKey);
	}
	
	function distinct_picker($strKey,$arrDefaultValues = array(),$boolCombo = false){
		$combo = ($boolCombo) ? ' class="combo"' : '';
		$cols = $this->objMar->distinct_values($strKey);
		foreach($arrDefaultValues as $d){
			foreach($cols as $k=>$v){
				if($v == $d) unset($cols[$k]);
			}
		}
		$cols = array_merge($arrDefaultValues,$cols);
/*
		$cols = @array_flip($cols);
		$cols = @array_flip($cols);
*/
		//@sort($cols);
		$out = '<select name="' . $strKey . '" size="1" id="' . $strKey . '"' . $combo . '>';
		foreach($cols as $col){
			$out .= '<option label = "' . h($col) . '" value="' . h($col) . '"';
			if($this->$strKey == $col) $out .= ' selected="selected"';
			$out .= '>' . h($col) . '</option>';
		}
		$out .= '</select>';
		return $out;
	}
	
	function child_picker($strClass, $intId, $boolCombo = false,$strLabelColumn = 'name'){
		$combo = ($boolCombo) ? ' class="combo"' : '';
		$strKey = $strClass . '_id';
		$strSortColumn = ($strClass == 'sizes' || $strClass == 'widths') ? 'id' : $strLabelColumn;
		$out = '<select size="1" name="' . $strKey . '" id="' . $strKey . '"' . $combo . '>';
		$out .= '<option value="" label=""></option>';
		$objects = $this->objMar->find_children($strClass,null,$strSortColumn . ' ASC');
		foreach($objects as $o) {
			$out .= '<option label="' . $o->h($strLabelColumn) . '" value="' . $o->id . '"';
			$out .= ($intId == $o->id) ? ' selected="selected"' : '';
			$out .= '>' . $o->h($strLabelColumn) . '</option>';
		}
		if($boolCombo) $out .= '<option value="Other..." label="Other...">Other...</option>';
		$out .= '</select>';
		if($boolCombo) $out .= '<script type="text/javascript" charset="utf-8">
			$("' . $strKey . '").observe("change",function(evt){
				document.bak = (document.bak) ? document.bak : {}
				if($F("' . $strKey . '") == "Other..."){
					var p = $("' . $strKey . '");
					var t = new Element("input",{"type": "text", "id": "' . $strKey . '", "name": "' . $strKey . '"})
					var w = p.getWidth;
					document.bak["' . $strKey . '"] = p.replace(t);
					t.focus();
					t.setStyle({width: w});
					t.observe("blur",function(){
						if($F("' . $strKey . '") == "" && document.bak["' . $strKey . '"]) {
							$("' . $strKey . '").replace(document.bak["' . $strKey . '"]);
							if(document.bak["' . $strKey . '"].options) document.bak["' . $strKey . '"].setValue(document.bak["' . $strKey . '"].options[0].value);
						}
					});
					
					
				}
			});
		</script>';
		return $out;
	}


	function parent_picker($strKey,$boolCombo = false){
		$combo = ($boolCombo) ? ' class="combo"' : '';
		$out = '<select size="1" name="' . $strKey . '" id="' . $strKey . '"' . $combo . '>';
		if($boolCombo) $out .= '<option value="" label=""></option>';
		$model = substr($strKey,0,-3);
		$columns = array_keys($this->objMar->Columns($model));
		array_shift($columns);
		$name = array_shift($columns);
		$objects = $this->objMar->FindAll($model,null,$name . ' ASC');
		foreach($objects as $o) {
			$out .= '<option label="' . $o->h($name) . '" value="' . $o->id . '"';
			$out .= ($this->$strKey == $o->$name) ? ' selected="selected"' : '';
			$out .= '>' . $o->h($name) . '</option>';
		}
		return $out . '</select>';
	}
	
	function parent_picker_filtered($strKey,&$objFilterObject, $strFilterKey, $strLabelKey, $boolUseKeyAsValue=false, $boolCombo = false){
		$found = $objFilterObject->find_children($strFilterKey);
		$steps = array();
		foreach($found as $f){
			$steps[$f->id] = $f->$strLabelKey;
		}
		return $this->picker($strKey,$steps,$boolUseKeyAsValue,$boolCombo);
	}
	
	function children_picker($strClass){
		//$out = '<select size="6" multiple="multiple" name="' . $strClass . '[]" id="' . $strClass . '">';
		$out = '<div class="multibox" id="' . $strClass . '">';
		$columns = array_keys($this->objMar->Columns($strClass));
		array_shift($columns);
		$name = array_shift($columns);
		$linked = $this->objMar->find_linked($strClass,null,$name . ' ASC');
		$all = $this->objMar->FindAll($strClass,null,$name . ' ASC');
		foreach($all as $o) {
			//$out .= '<option label="' . $o->h($name) . '" value="' . $o->id . '"';
			$out .= '<label for="' . $strClass . '_' . $o->id . '"><input type="checkbox" id="' . $strClass . '_' . $o->id . '" name="' . $strClass . '[]" value="' . $o->id . '"';
			$out .= (in_array($o->id,array_keys($linked))) ? ' checked="checked"' : '';
			$out .= '/>' . $o->label() . '</label>';
			//$out .= (in_array($o->id,array_keys($linked))) ? ' selected="selected"' : '';
			//$out .= '>' . $o->h($name) . '</option>';
		}
		$out .= '<label for="' . $strClass . '_add" class="addToCombo"><img src="/icns/add.png" id="' . $strClass . '_add" class="addToCombo" name="' . $strClass . '_add" width="16" height="16" alt="add option" /> add</label>';
		//return $out . '</select>';
		return $out . '</div>';
	}
	
	function flash($arrMessages,$strClass=''){
		if(empty($strClass)) $strClass = 'flash';
		$out = '<ul class="' . $strClass . '">';
		foreach($arrMessages as $m) $out .=  '<li>' . $m . '</li>';
		return $out . '</ul>';
	}
	function form($method='post', $action=''){
		$id = ($this->objMar->id > 0) ? get_class($this->objMar) . '_' . $this->id . '_form' : 'scaffold_form';
		$form = '<form id="' . $id . '" method="' . $method . '" action="' . $action . '"><ul>';
		$model = get_class($this->objMar);
		$delete = ($this->objMar->id > 0) ? ' <input type="submit" name="delete" value="Delete" />' : '';
		foreach($this->_public as $k=>$col){
			$field = '';
			if($col['Type'] == 'text'){
				$field = '<textarea name="' . $k . '" rows="8" cols="24">' . $this->$k . '</textarea>';
			}elseif(substr($col['Type'],0,3) == 'set'){
				$options = explode(',',str_replace("'",'',substr($col['Type'],4,-1)));
				$field = $this->picker($k,$options);
			}elseif(stristr($col['Type'],'char') !== false){
				$field = '<input type="text" name="' . $k . '" value="' . $this->$k . '" />';
			}elseif(stristr($col['Type'],'int') !== false || stristr($col['Type'],'decimal') !== false || stristr($col['Type'],'float') !== false || stristr($col['Type'],'double') !== false || stristr($col['Type'],'date') !== false || stristr($col['Type'],'time') !== false || stristr($col['Type'],'year') !== false){
				$field = (substr($k,-3) == '_id') ? $this->parent_picker($k) : '<input type="text" name="' . $k . '" value="' . $this->$k . '" />';
			}
		    $form .= ($field) ? '<li><label for="' . $k . '">' . $k . '</label>' . $field . '</li>' : '';
		}
		$class = get_class($this->objMar);
		foreach($this->objMar->Tables() as $t){
			if (stristr($t,$class) && stristr($t,'_')){
				$foreign_class = preg_replace('/_?' . $class . '_?/i','',$t);
				$form .= '<li><label for="' . $foreign_class . '">' . $foreign_class . '</label>' . $this->children_picker($foreign_class) . '</li>';
			}
		}
		$form .= '<li><label for="save">&nbsp;</label><input type="submit" name="save" value="Save" />' . $delete . ' <a class="back_to_list" href="http://' . $_SERVER['SERVER_NAME'] . '/' . $model . '/list">Back to List</a></li></ul></form>';
		return $form;
	}
	function element($strTag,$mxdSource,$mxdField,$strClass='',$strId=''){
		$out = '';
		switch ($strTag) {
			case 'ul':
			case 'ol':
				$child = 'li';
				break;
			case 'div':
				$child = '';
				break;
			default:
				$child = 'span';
				break;
		}
		if(is_array($mxdSource)){
			foreach($mxdSource as $k=>$objMar){
				$id = ($strId) ? $strId . '_' . $k : $strTag . '_' . $k;
				$out .= $this->element($strTag,$objMar,$mxdField,$strClass,$id);
			}
		}else{
			$out .= "\n<" . $strTag;
			$out .= ($strClass) ? ' class="' . $strClass . '"' : '';
			$out .= ($strId) ? ' id="' . $strId . '"' : '';
			$out .= ">\n";
			foreach($mxdField as $f){
				if(!empty($child)){
					$out .= '<' . $child;
					$out .= ($strClass) ? ' class="' . $strClass . '"' : '';
					$out .= ($strId) ? ' id="' . $strId . '"' : '';
					$out .= '>';
					$out .= $this->$f;
				}else{
					$out .= Markdown($this->$f);
				}
				$out .= ($child) ? '</' . $child . '>' : '';
			}
			$out .= '</' . $strTag . ">\n";
		}
		return $out;
	}

	function div($arrFields = array()){
		if(count($arrFields) == 0) {
			$arrFields = array_keys($this->_public);
		}
		return $this->element('div',$this->objMar,$arrFields);
	}
	function li($arrFields = array()){
		if(count($arrFields) == 0) {
			$arrFields = array_keys($this->_public);
			$arrFields[0] = 'edit_link,name';
		}
		return $this->element('li',$this->objMar,$arrFields);
	}
	function edit_link($mxdKey = null){
		if(empty($mxdKey)){
			$link_text = 'edit';
		}else{
			$link_text = $this->$mxdKey;
		}
		return $this->link_for('edit');
	}

}
?>