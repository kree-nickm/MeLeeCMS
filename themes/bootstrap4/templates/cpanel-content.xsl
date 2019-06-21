<?xml version="1.0"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html"/>

<!-- This is the XSL for the boxes that represent the page content. These pop up when "Add Content"
is clicked. "cpanel-page-content.js" uses this block when it dynamically adds more boxes to the page.
The "mode" attribute must not be changed without updating both the XSL above and that JavaScript file. -->
<xsl:template match="content[@page_content]" mode="page-content">
<xsl:param name="id_prefix">content_</xsl:param>
<xsl:param name="name_prefix">content</xsl:param>
<xsl:param name="id"><xsl:value-of select="id"/><xsl:value-of select="random"/></xsl:param>
<!-- id and $id will be the same unless $id is provided via param. That should only be the case for JavaScript-added elements that need to generate an ID for uniqueness purposes, but not actually display it in the ID input since it is arbitrary. -->
<xsl:element name="div">
	<xsl:attribute name="class">
		<xsl:choose>
			<xsl:when test="class='Container'">page-content mb-4 col-12</xsl:when>
			<xsl:otherwise>page-content mb-4 col-md-6 col-xl-4</xsl:otherwise>
		</xsl:choose>
	</xsl:attribute>
	<xsl:element name="div">
		<xsl:attribute name="class">
			<xsl:choose>
				<xsl:when test="class='Container'">card border-dark</xsl:when>
				<xsl:otherwise>card border-secondary</xsl:otherwise>
			</xsl:choose>
		</xsl:attribute>
		<xsl:element name="h5">
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="class='Container'">card-header text-white bg-dark</xsl:when>
					<xsl:otherwise>card-header text-white bg-secondary</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<xsl:value-of select="class"/> Content
			<button type="button" class="text-danger ml-3 remove-content" aria-label="Remove">
				<span class="fas fa-times" aria-hidden="true"></span>
			</button>
			<button type="button" class="text-white mx-2 move-content-down" aria-label="Move down">
				<span class="fas fa-arrow-down" aria-hidden="true"></span>
			</button>
			<button type="button" class="text-white mx-2 move-content-up" aria-label="Move up">
				<span class="fas fa-arrow-up" aria-hidden="true"></span>
			</button>
		</xsl:element>
		<ul class="list-group list-group-flush">
			<li class="list-group-item">
				<div class="input-group input-group-sm">
					<label for="{$id_prefix}{$id}_id" class="input-group-prepend">
						<span class="input-group-text">ID</span>
					</label>
					<input id="{$id_prefix}{$id}_id" type="text" class="form-control" name="{$name_prefix}[][content_id]" value="{id}" placeholder="unique_id" title="Content identifier." aria-describedby="{$id_prefix}{$id}_iddesc" />
				</div>
				<small id="{$id_prefix}{$id}_iddesc" class="form-text text-muted">A string that uniquely identifies this content within its parent container, for use by the theme.</small>
			</li>
			<input type="hidden" readonly="readonly" name="{$name_prefix}[][content_class]" value="{class}"/>
			<xsl:apply-templates select="property[@type!='']" mode="page-property">
				<xsl:with-param name="id_prefix" select="$id_prefix"/>
				<xsl:with-param name="name_prefix" select="$name_prefix"/>
				<xsl:with-param name="id" select="$id"/>
			</xsl:apply-templates>
		</ul>
	</xsl:element>
</xsl:element>
</xsl:template>

<xsl:template match="property" mode="page-property">
<xsl:param name="id_prefix">content_</xsl:param>
<xsl:param name="name_prefix">content</xsl:param>
<xsl:param name="id"/>
<li class="list-group-item content-property">
	<xsl:choose>
		<xsl:when test="@type='paragraph'">
			<label for="{$id_prefix}{$id}_{name}"><b>Property: </b><xsl:value-of select="name"/></label>
			<xsl:call-template name="textarea">
				<xsl:with-param name="id_prefix" select="$id_prefix"/>
				<xsl:with-param name="name_prefix" select="$name_prefix"/>
				<xsl:with-param name="id" select="$id"/>
			</xsl:call-template>
			<small id="{$id_prefix}{$id}_{name}desc" class="form-text text-muted"><xsl:value-of select="desc"/></small>
		</xsl:when>
		<xsl:when test="@type='string'">
			<label for="{$id_prefix}{$id}_{name}"><b>Property: </b><xsl:value-of select="name"/></label>
			<xsl:call-template name="text_input">
				<xsl:with-param name="id_prefix" select="$id_prefix"/>
				<xsl:with-param name="name_prefix" select="$name_prefix"/>
				<xsl:with-param name="id" select="$id"/>
			</xsl:call-template>
			<small id="{$id_prefix}{$id}_{name}desc" class="form-text text-muted"><xsl:value-of select="desc"/></small>
		</xsl:when>
		<xsl:when test="@type='dictionary'">
			<label><b>Property: </b><xsl:value-of select="name"/></label>
			<button type="button" class="float-right btn btn-sm btn-secondary add-dict" data-prop-name="{name}" data-content-id="{$id}" data-id-prefix="{$id_prefix}" data-name-prefix="{$name_prefix}">Add Entry</button>
			<div class="dictionary-container">
				<xsl:call-template name="dictionary">
					<xsl:with-param name="id_prefix" select="$id_prefix"/>
					<xsl:with-param name="name_prefix" select="$name_prefix"/>
					<xsl:with-param name="id" select="$id"/>
				</xsl:call-template>
			</div>
			<small id="{$id_prefix}{$id}_{name}desc" class="form-text text-muted"><xsl:value-of select="desc"/></small>
		</xsl:when>
		<xsl:when test="@type='container'">
			<label for="{$id_prefix}{$id}_{name}"><b>Property: </b><xsl:value-of select="name"/></label>
			<div class="float-right input-group input-group-sm add-content" style="width:initial;">
				<select id="{$id_prefix}{$id}_{name}" class="form-control" style="flex:none;width:initial;" aria-label="Add container content">
					<xsl:for-each select="/MeLeeCMS/content[@id='content-classes']/class">
						<option value="{.}"><xsl:value-of select="."/></option>
					</xsl:for-each>
				</select>
				<div class="input-group-append">
					<button class="btn btn-secondary" type="button">Add Content</button>
				</div>
			</div>
			<small id="{$id_prefix}{$id}_{name}desc" class="form-text text-muted"><xsl:value-of select="desc"/></small>
			<div class="content-container row" data-id-prefix="{$id_prefix}{$id}_" data-name-prefix="{$name_prefix}[][{name}]">
				<xsl:call-template name="container">
					<xsl:with-param name="id_prefix" select="$id_prefix"/>
					<xsl:with-param name="name_prefix" select="$name_prefix"/>
					<xsl:with-param name="id" select="$id"/>
				</xsl:call-template>
			</div>
		</xsl:when>
		<xsl:when test="@type='component'">
			<label for="{$id_prefix}{$id}_{name}"><b>Property: </b><xsl:value-of select="name"/> (component)</label>
			<xsl:call-template name="component">
				<xsl:with-param name="id_prefix" select="$id_prefix"/>
				<xsl:with-param name="name_prefix" select="$name_prefix"/>
				<xsl:with-param name="id" select="$id"/>
			</xsl:call-template>
			<small id="{$id_prefix}{$id}_{name}desc" class="form-text text-muted"><xsl:value-of select="desc"/></small>
		</xsl:when>
		<xsl:when test="@type='database_table'">
			<label for="{$id_prefix}{$id}_{name}"><b>Property: </b><xsl:value-of select="name"/></label>
			<xsl:call-template name="database_table">
				<xsl:with-param name="id_prefix" select="$id_prefix"/>
				<xsl:with-param name="name_prefix" select="$name_prefix"/>
				<xsl:with-param name="id" select="$id"/>
			</xsl:call-template>
			<small id="{$id_prefix}{$id}_{name}desc" class="form-text text-muted"><xsl:value-of select="desc"/></small>
		</xsl:when>
		<xsl:otherwise>
			<br/>Unhandled property type "<xsl:value-of select="@type"/>" with value "<xsl:value-of select="value"/>".
			<small id="{$id_prefix}{$id}_{name}desc" class="form-text text-muted"><xsl:value-of select="desc"/></small>
		</xsl:otherwise>
	</xsl:choose>
</li>
<xsl:if test="@type='database_table'">
	<xsl:call-template name="database_config">
		<xsl:with-param name="id_prefix" select="$id_prefix"/>
		<xsl:with-param name="name_prefix" select="$name_prefix"/>
		<xsl:with-param name="id" select="$id"/>
		<xsl:with-param name="table" select="value"/>
	</xsl:call-template>
</xsl:if>
</xsl:template>

<!-- BEGIN Specific input type definitions for the property inputs. -->
<xsl:template name="text_input">
<xsl:param name="id_prefix">content_</xsl:param>
<xsl:param name="name_prefix">content</xsl:param>
<xsl:param name="id"/>
<input type="text" class="form-control" id="{$id_prefix}{$id}_{name}" aria-describedby="{$id_prefix}{$id}_{name}desc" placeholder="" name="{$name_prefix}[][{name}]" value="{value}"/>
</xsl:template>

<xsl:template name="textarea">
<xsl:param name="id_prefix">content_</xsl:param>
<xsl:param name="name_prefix">content</xsl:param>
<xsl:param name="id"/>
<textarea class="form-control" id="{$id_prefix}{$id}_{name}" aria-describedby="{$id_prefix}{$id}_{name}desc" name="{$name_prefix}[][{name}]">
	<xsl:value-of select="value"/>
</textarea>
</xsl:template>

<xsl:template name="dictionary">
<xsl:param name="id_prefix">content_</xsl:param>
<xsl:param name="name_prefix">content</xsl:param>
<xsl:param name="id"/>
<xsl:for-each select="value">
	<xsl:call-template name="dictionary_entry">
		<xsl:with-param name="id_prefix" select="$id_prefix"/>
		<xsl:with-param name="name_prefix" select="$name_prefix"/>
		<xsl:with-param name="id" select="$id"/>
		<xsl:with-param name="name" select="../name"/>
	</xsl:call-template>
</xsl:for-each>
</xsl:template>

<xsl:template name="dictionary_entry">
<xsl:param name="id_prefix">content_</xsl:param>
<xsl:param name="name_prefix">content</xsl:param>
<xsl:param name="id"/>
<xsl:param name="name"/>
<div class="dictionary-entry input-group input-group-sm my-1">
	<input type="text" class="form-control" aria-describedby="{$id_prefix}{$id}_{$name}desc" placeholder="key" name="{$name_prefix}[][{$name}][][key]" value="{@key}"/>
	<input type="text" class="form-control" aria-describedby="{$id_prefix}{$id}_{$name}desc" placeholder="value" name="{$name_prefix}[][{$name}][][value]" value="{.}"/>
	<div class="input-group-append">
		<button class="btn btn-outline-danger remove-dict" type="button"><span class="fas fa-times" aria-hidden="true"></span></button>
	</div>
</div>
</xsl:template>

<xsl:template name="container">
<xsl:param name="id_prefix">content_</xsl:param>
<xsl:param name="name_prefix">content</xsl:param>
<xsl:param name="id"/>
<xsl:apply-templates select="content[@page_content]" mode="page-content">
	<xsl:with-param name="id_prefix"><xsl:value-of select="$id_prefix"/><xsl:value-of select="$id"/>_</xsl:with-param>
	<xsl:with-param name="name_prefix"><xsl:value-of select="$name_prefix"/>[][<xsl:value-of select="name"/>]</xsl:with-param>
</xsl:apply-templates>
</xsl:template>

<xsl:template name="component">
<xsl:param name="id_prefix">content_</xsl:param>
<xsl:param name="name_prefix">content</xsl:param>
<xsl:param name="id"/>
<xsl:variable name="value" select="value"/>
<select id="{$id_prefix}{$id}_{name}" class="form-control" name="{$name_prefix}[][{name}]">
	<xsl:for-each select="/MeLeeCMS/content[@id='component-list']/component">
		<xsl:element name="option">
			<xsl:attribute name="value"><xsl:value-of select="index"/></xsl:attribute>
			<xsl:if test="$value=index"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>
			<xsl:value-of select="index"/>. <xsl:value-of select="title"/>
		</xsl:element>
	</xsl:for-each>
</select>
</xsl:template>

<xsl:template name="database_table">
<xsl:param name="id_prefix">content_</xsl:param>
<xsl:param name="name_prefix">content</xsl:param>
<xsl:param name="id"/>
<div class="input-group">
	<xsl:if test="value!=''">
		<input id="{$id_prefix}{$id}_{name}" name="{$name_prefix}[][{name}]" class="form-control" title="Table cannot be changed once it is configured. Add new content to use a different table." value="{value}" readonly="true"/>
		<div class="input-group-append">
			<button class="btn btn-secondary db-config" type="button" data-toggle="modal" data-target="#{$id_prefix}{$id}_dbconfig" data-table="{value}">Configure</button>
		</div>
	</xsl:if>
	<xsl:if test="value=''">
		<select id="{$id_prefix}{$id}_{name}" class="form-control" name="{$name_prefix}[][{name}]">
			<xsl:for-each select="/MeLeeCMS/content[@id='dbtable-list']/table">
				<xsl:element name="option">
					<xsl:attribute name="value"><xsl:value-of select="name"/></xsl:attribute>
					<xsl:value-of select="name"/>
				</xsl:element>
			</xsl:for-each>
		</select>
		<div class="input-group-append">
			<button class="btn btn-secondary" type="button" disabled="true" title="Save the page to configure this content.">Configure</button>
		</div>
	</xsl:if>
</div>
</xsl:template>

<xsl:template name="database_config">
<xsl:param name="id_prefix">content_</xsl:param>
<xsl:param name="name_prefix">content</xsl:param>
<xsl:param name="id"/>
<xsl:param name="table"/>
<xsl:variable name="config" select="../property[name='config']/value"/>
<div class="modal fade db-config" id="{$id_prefix}{$id}_dbconfig" tabindex="-1" role="dialog" aria-labelledby="{$id_prefix}{$id}_dbconfig_label" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content border-dark">
			<div class="modal-header text-white bg-dark">
				<h3 class="modal-title" id="{$id_prefix}{$id}_dbconfig_label">Database Table View Configuration</h3>
				<button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
					<span class="fas fa-times" aria-hidden="true"></span>
				</button>
			</div>
			<div class="modal-body row">
				<xsl:for-each select="/MeLeeCMS/content[@id='dbtable-list']/table[name=$table]/column">
					<xsl:variable name="namestr"><xsl:value-of select="name"/></xsl:variable>
					<div class="input-group col-12">
						<div class="input-group-prepend">
							<input class="form-control" value="{name}" readonly="true"/>
						</div>
						<select id="{$id_prefix}{$id}_dbconfig_filter{name}_output" name="{$name_prefix}[][{name}_output]" class="form-control" style="flex:none;width:initial;">
							<xsl:element name="option">
								<xsl:attribute name="value"></xsl:attribute>
								<xsl:if test="not($config/columns[name=$namestr])"><xsl:attribute name="selected"/></xsl:if>
								Don't Include
							</xsl:element>
							<xsl:element name="option">
								<xsl:attribute name="value">raw</xsl:attribute>
								<xsl:if test="$config/columns[name=$namestr]/output='raw'"><xsl:attribute name="selected"/></xsl:if>
								Include Normally 
							</xsl:element>
							<xsl:element name="option">
								<xsl:attribute name="value">json</xsl:attribute>
								<xsl:if test="$config/columns[name=$namestr]/output='json'"><xsl:attribute name="selected"/></xsl:if>
								Decode JSON
							</xsl:element>
						</select>
					</div>
				</xsl:for-each>
				<div class="table-responsive">
					<table class="table table-hover">
						<thead class="thead-light">
							<tr>
								<th scope="col">Column</th>
								<th scope="col"></th>
								<th scope="col">Value</th>
								<th scope="col">Type</th>
							</tr>
						</thead>
						<tbody>
						<xsl:for-each select="/MeLeeCMS/content[@id='dbtable-list']/table[name=$table]/column">
							<xsl:variable name="namestr"><xsl:value-of select="name"/></xsl:variable>
							<tr>
								<td scope="row"><input class="form-control" value="{name}" readonly="true"/></td>
								<td><select id="{$id_prefix}{$id}_dbconfig_filter{name}_comp" name="{$name_prefix}[][{name}_comp]" class="form-control" style="flex:none;width:initial;">
									<option value=''>N/A</option>
									<xsl:choose>
										<xsl:when test="type='tinyint' or type='smallint' or type='mediumint' or type='int' or type='integer' or type='bigint' or type='bit' or type='year' or type='decimal' or type='numeric' or type='float' or type='double'">
											<xsl:element name="option">
												<xsl:attribute name="value">=</xsl:attribute>
												<xsl:if test="$config/filters[column=$namestr]/comparator='='"><xsl:attribute name="selected"/></xsl:if>
												=
											</xsl:element>
											<xsl:element name="option">
												<xsl:attribute name="value">&gt;</xsl:attribute>
												<xsl:if test="$config/filters[column=$namestr]/comparator='&gt;'"><xsl:attribute name="selected"/></xsl:if>
												&gt;
											</xsl:element>
											<xsl:element name="option">
												<xsl:attribute name="value">&lt;</xsl:attribute>
												<xsl:if test="$config/filters[column=$namestr]/comparator='&lt;'"><xsl:attribute name="selected"/></xsl:if>
												&lt;
											</xsl:element>
											<xsl:element name="option">
												<xsl:attribute name="value">&gt;=</xsl:attribute>
												<xsl:if test="$config/filters[column=$namestr]/comparator='&gt;='"><xsl:attribute name="selected"/></xsl:if>
												&gt;=
											</xsl:element>
											<xsl:element name="option">
												<xsl:attribute name="value">&lt;=</xsl:attribute>
												<xsl:if test="$config/filters[column=$namestr]/comparator='&lt;='"><xsl:attribute name="selected"/></xsl:if>
												&lt;=
											</xsl:element>
										</xsl:when>
										<xsl:otherwise>
											<xsl:element name="option">
												<xsl:attribute name="value">=</xsl:attribute>
												<xsl:if test="$config/filters[column=$namestr]/comparator='='"><xsl:attribute name="selected"/></xsl:if>
												=
											</xsl:element>
										</xsl:otherwise>
									</xsl:choose>
								</select></td>
								<td><input id="{$id_prefix}{$id}_dbconfig_filter{name}_value" name="{$name_prefix}[][{name}_value]" class="form-control" value="{$config/filters[column=$namestr]/value}"/></td>
								<td><select id="{$id_prefix}{$id}_dbconfig_filter{name}_type" name="{$name_prefix}[][{name}_type]" class="form-control" style="flex:none;width:initial;">
									<xsl:element name="option">
										<xsl:attribute name="value">raw</xsl:attribute>
										<xsl:if test="$config/filters[column=$namestr]/type='raw'"><xsl:attribute name="selected"/></xsl:if>
										Raw
									</xsl:element>
									<xsl:element name="option">
										<xsl:attribute name="value">post</xsl:attribute>
										<xsl:if test="$config/filters[column=$namestr]/type='post'"><xsl:attribute name="selected"/></xsl:if>
										$_POST
									</xsl:element>
									<xsl:element name="option">
										<xsl:attribute name="value">get</xsl:attribute>
										<xsl:if test="$config/filters[column=$namestr]/type='get'"><xsl:attribute name="selected"/></xsl:if>
										$_GET
									</xsl:element>
									<xsl:element name="option">
										<xsl:attribute name="value">request</xsl:attribute>
										<xsl:if test="$config/filters[column=$namestr]/type='request'"><xsl:attribute name="selected"/></xsl:if>
										$_REQUEST
									</xsl:element>
								</select></td>
							</tr>
						</xsl:for-each>
						</tbody>
					</table>
				</div>
			</div>
			<div class="modal-footer">
				<i style="float:left;">Changes will be saved when you save the page.</i>
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>
</xsl:template>
<!-- END Specific input type definitions for the property inputs. -->

</xsl:stylesheet>
