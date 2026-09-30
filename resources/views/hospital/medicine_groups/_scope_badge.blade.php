@if(in_array($group->usage_scope, ['opd', 'ot'], true))
    <span class="gti-scope-badge gti-scope-{{ $group->usage_scope }}">{{ strtoupper($group->usage_scope) }}</span>
@endif
