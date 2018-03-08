
<h3 class="header smaller lighter blue mg-top12 mg-bottom20">发布新书</h3>

<form class="form-horizontal" action="/manage/addBook" method="post" enctype="multipart/form-data">
    {{csrf_field()}}
    <div class="g-backrealdetails clearfix bor-border interface">
        <div class="space-8 col-xs-12"></div>
        <div class="form-group interface-bottom col-xs-12">
            <label class="col-sm-1 text-right">书籍名称：</label>
            <div class="text-left col-sm-9">
                <input type="text" name="book_name" id="book_name">
            </div>
        </div>

        <div class="form-group interface-bottom col-xs-12">
            <label class="col-sm-1 text-right">封面：</label>
            <div class="text-left col-sm-9">
                <input type="file" name="book_cover" id="book_cover">
            </div>
        </div>

        <div class="form-group interface-bottom col-xs-12">
            <label class="col-sm-1 text-right">大纲：</label>
            <div class="text-left col-sm-8">
                <!--编辑器-->
                <div class="clearfix">
                    <script id="editor" name="book_synopsis"  type="text/plain" style="height:300px;" ></script>
                </div>
            </div>
        </div>
        <div class="form-group interface-bottom col-xs-12">
            <lebel class="col-sm-1 text-right">书籍类型：</lebel>
            <div class="text-left col-sm-9">
                <select name="book_type">
                    <option value="">请选择</option>
                    @foreach($book_type as $item)
                        <option value="{{$item['id']}}">{{ $item['name'] }}</option>
                    @endforeach

                </select>
            </div>
        </div>

        <div class="col-xs-12">
            <div class="clearfix row bg-backf5 padding20 mg-margin12">
                <div class="col-xs-12">
                    <div class="col-sm-1 text-right"></div>
                    <div class="col-sm-10"><button type="submit" class="btn btn-sm btn-primary">提交</button></div>
                </div>
            </div>
        </div>

    </div>
</form>



{!! Theme::widget('ueditor')->render() !!}
{!! Theme::asset()->container('custom-css')->usepath()->add('backstage', 'css/backstage/backstage.css') !!}
{!! Theme::asset()->container('specific-js')->usepath()->add('custom', 'plugins/ace/js/jquery-ui.custom.min.js') !!}
