        <div id="results">
            <p class="text-result-summary">You searched for <strong>$Query</strong></p>
            <% if $Results %>
                <p class="text-result-summary">Showing {$StartResult}-{$EndResult} of $TotalResults result<% if $TotalResults != 1 %>s<% end_if %>. </p>

                <!-- START search results -->
                <ul id="SearchResults">
                    <% loop $Results %>
                    <li>
                        <h3><a href="$Link" class="searchResultHeader">$Title</a></h3>
                        $Content.SearchTextHighlight(25)
                        <a href="$Link" title="Read more about &quot;{$Title}&quot;" class="readMoreLink">Read more about &quot;{$Title}&quot;</a>
                    </li>
                    <% end_loop %>
                </ul>
                <!-- END search results -->
                <p class="text-result-summary">Showing {$StartResult}-{$EndResult} of $TotalResults result<% if $TotalResults != 1 %>s<% end_if %>. </p>

                <% if $SearchPages %>
                <!-- START pagination -->
                    <ul class="pagination">
                        <% if $PrevUrl=false %><% else %>
                        <li class="prev"><a href="$PrevUrl">Prev</a></li>
                        <% end_if %>               
                    <% loop $SearchPages %>
                        <% if $IsEllipsis %>
                        <li class="ellipsis">...</li>
                        <% else %>
                            <% if $Current %>
                            <li class="active"><strong>$PageNumber</strong></li>
                            <% else %>
                            <li><a href="$Link">$PageNumber</a></li>
                            <% end_if %>
                        <% end_if %>
                    <% end_loop %>
                        <% if $NextUrl=false %><% else %>
                        <li class="next"><a href="$NextUrl">Next</a></li>
                        <% end_if %>               
                    </ul>
                <!-- END pagination -->                
                <% end_if %>
            <% else %>
                <p>No results.</p>
            <% end_if %>
        </div>

