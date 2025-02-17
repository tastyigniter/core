---
title: Components
description: ''
permalink: /components
layout: default
'[testComponent]': []
'[test::livewire-component]': []
---
<div class="container">
    <div class="row">
        @push('scripts')
            <p>This is a stack</p>
        @endpush

        @push('scripts')
            <p>This is a stack</p>
        @endpush

        @adminauth()
        <p>This is a logged admin</p>
        @endadminauth

        @mainauth()
        <p>This is a logged customer</p>
        @endmainauth

        @themePartialIf('scripts')

        @themeComponent('testComponent')
    </div>
</div>