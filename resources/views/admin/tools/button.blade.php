{{--<ul class="pagination pagination-sm no-margin pull-right">--}}
    {{--<!-- Previous Page Link -->--}}

        {{--<li class="page-item disabled"><span class="page-link">&laquo;</span></li>--}}

<!-- Pagination Elements -->
    {{--@foreach ($elements as $element)--}}
    {{--<!-- "Three Dots" Separator -->--}}
        {{--@if (is_string($element))--}}
            {{--<li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>--}}
        {{--@endif--}}

    {{--<!-- Array Of Links -->--}}
        {{--@if (is_array($element))--}}
            {{--@foreach ($element as $page => $url)--}}
                {{--@if ($page == $paginator->currentPage())--}}
                    {{--<li class="page-item active"><span class="page-link">{{ $page }}</span></li>--}}
                {{--@else--}}
                    {{--<li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>--}}
                {{--@endif--}}
            {{--@endforeach--}}
        {{--@endif--}}
    {{--@endforeach--}}

<!-- Next Page Link -->


        {{--<li class="page-item disabled"><span class="page-link">&raquo;</span></li>--}}
{{--</ul>--}}
<div style="float: right">
    {{ $paginator->links() }}
</div>


