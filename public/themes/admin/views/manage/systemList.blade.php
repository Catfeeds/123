
<h3 class="header smaller lighter blue mg-top12 mg-bottom20">系统任务</h3>
<div class="widget-header mg-bottom20 mg-top12 widget-well">
    <div class="widget-toolbar no-border pull-left no-padding">
        <ul class="nav nav-tabs">
            <li class="active">
                <a data-toggle="tab" href="#basic">系统任务管理</a>
            </li>

            <li class="">
                <a  href="/manage/systemAdd">添加系统任务</a>
            </li>
        </ul>
    </div>
</div>

<div class="row">
    <div class="col-xs-12">
        <div>
            <table id="sample-table" class="table table-striped table-bordered table-hover">
                <thead>
                <tr>
                    <th class="center"></th>
                    <th>编号</th>
                    <th>任务名称</th>
                    <th>任务等级</th>
                    <th>任务类型</th>
                    <th>任务时限</th>
                    <th>奖励类型</th>
                    <th>奖励额</th>
                    <th>添加时间</th>
                    <th>操作</th>
                </tr>
                </thead>
                <form action="/manage/systemHandle" method="post" id="FromSubmit">
                    {!! csrf_field() !!}
                    <tbody>
                    @if(!empty($system_task))
                    @foreach($system_task as $item)
                        <tr>
                            <td class="center">
                                <label class="pos-rel">
                                    <input type="checkbox"  class="ace check" name="ckb[]" value="{!! $item->id !!}"/>
                                    <span class="lbl"></span>
                                </label>
                            </td>
                            <td>{!! $item->id !!}</td>
                            <td>{!! $item->name !!}</td>
                            <td>{!! $item->grade_name !!}</td>
                            <td>{!! $item->task_type !!}</td>
                            <td>{!! $item->time_limit !!}</td>
                            <td>@if($item->reward_type == 1) 余额 @else 金币 @endif</td>
                            <td>{!! $item->reward_amount !!}</td>
                            <td>{!! $item->create_at !!}</td>
                            <td>
                                <a class="btn btn-xs btn-info" href="systemDet/{{ $item['id'] }}">
                                    <i class="fa fa-edit bigger-120"></i>编辑
                                </a>
                                <a  href="systemDel/{{ $item['id'] }}" title="删除" class="btn btn-xs btn-danger">
                                    <i class="ace-icon fa fa-trash-o bigger-120"></i>删除
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    @endif
                    </tbody>
                </form>
            </table>
        </div>
        <div class="row">
            <div class="col-xs-12">
                <div class="dataTables_info" id="sample-table-2_info" role="status" aria-live="polite">
                	<label class="position-relative mg-right10">
                        <input type="checkbox" class="ace" id="checkAll" value="1" />
                        <span class="lbl"> 全选</span>
                    </label>
                    <button type="submit" id="allTaskHandle" class="btn btn-primary btn-sm">批量删除</button>
                </div>
            </div>
            <div class="space-10 col-xs-12"></div>
            <div class="col-xs-12">
                <div class="dataTables_paginate paging_simple_numbers row" id="dynamic-table_paginate">
                    {!! $system_task->render() !!}
                </div>
            </div>
        </div>
    </div>
</div><!-- /.row -->
<script>
    //全选
	$len=$('.check').length;
	$('#checkAll').on('click',function(){
		
		if($(this).val() ==1){
			for(var i=0 ;i<$len;i++){
				$('.check')[i].checked=true;
			}
			
			$(this).val(2);
		}else{
			for(var i=0 ;i<$len;i++){
				$('.check')[i].checked=false;
			}
			$(this).val(1);
		}		
	})
    //批量审核
  $('#allTaskHandle').on('click',function(){
	  $('#FromSubmit').submit();
  })	
</script>

{!! Theme::asset()->container('custom-css')->usepath()->add('backstage', 'css/backstage/backstage.css') !!}

{{--时间插件--}}
{!! Theme::asset()->container('specific-css')->usePath()->add('datepicker-css', 'plugins/ace/css/datepicker.css') !!}
{!! Theme::asset()->container('specific-js')->usePath()->add('datepicker-js', 'plugins/ace/js/date-time/bootstrap-datepicker.min.js') !!}
{!! Theme::asset()->container('custom-js')->usePath()->add('userfinance-js', 'js/userfinance.js') !!}

{!! Theme::asset()->container('custom-js')->usePath()->add('checked-js', 'js/checkedAll.js') !!}