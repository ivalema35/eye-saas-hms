{{--
  Shared permission matrix checkboxes for Role create/edit.
  Expects: $modules (from RolePermissionService::getPermissionsForRoleUI)
  Optional: $role (edit)
--}}
@php
    $isSuper = (bool) ($role->is_super ?? false);
    $oldPerms = old('permissions');
@endphp

<div class="permission-card">
    <div class="table-responsive">
        <table class="permission-table">
            <tbody>
                @foreach($modules as $moduleKey => $module)
                    <tr class="perm-row">
                        <td class="module-label">
                            <i class="fa-solid fa-folder-open me-2 text-primary"></i>
                            {{ $module['label'] ?? str_replace('_', ' ', $moduleKey) }}
                        </td>
                        <td class="perm-container">
                            @foreach(($module['features'] ?? []) as $featureKey => $feature)
                                <div class="perm-feature-block" style="width:100%;margin-bottom:.55rem">
                                    <div class="perm-feature-label" style="font-size:.72rem;font-weight:850;letter-spacing:.04em;text-transform:uppercase;color:rgba(18,60,90,.55);margin-bottom:.35rem">
                                        {{ $feature['label'] ?? $featureKey }}
                                    </div>
                                    <div style="display:flex;flex-wrap:wrap;gap:10px">
                                        @foreach(($feature['permissions'] ?? []) as $perm)
                                            @php
                                                if ($isSuper) {
                                                    $checked = true;
                                                } elseif (is_array($oldPerms)) {
                                                    $checked = in_array((string) $perm['id'], array_map('strval', $oldPerms), true);
                                                } elseif (! isset($role) && str_starts_with((string) ($perm['action'] ?? ''), 'dashboard_')) {
                                                    // New role: Dashboard widgets ON by default (empty data still shows UI as 0)
                                                    $checked = true;
                                                } else {
                                                    $checked = (bool) ($perm['is_granted'] ?? false);
                                                }
                                            @endphp
                                            <label class="perm-badge {{ $isSuper ? 'is-super' : '' }}">
                                                <input type="checkbox"
                                                       class="perm-checkbox"
                                                       name="permissions[]"
                                                       value="{{ $perm['id'] }}"
                                                       {{ $checked ? 'checked' : '' }}
                                                       {{ $isSuper ? 'disabled' : '' }}>
                                                <span>{{ $perm['label'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
