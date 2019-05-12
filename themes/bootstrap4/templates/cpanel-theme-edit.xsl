<?xml version="1.0"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html"/>

<xsl:template match="content[@id='theme-edit']" priority="1">
<form id="form_edit_theme" action="" method="post">
	<div class="container my-4">
		<noscript><div class="alert alert-danger" role="alert">The page editor requires JavaScript in order to function.</div></noscript>
		<div class="card border-dark">
			<div class="card-header text-white bg-dark">
				<h4 class="card-title">Edit Theme <xsl:value-of select="content[@id='main']/@name"/></h4>
				<h6 class="card-subtitle text-muted">Make changes to the properties and content of this theme.</h6>
			</div>
			<div class="card-body row">
			</div>
		</div>
	</div>
</form>

<div class="container">
	<div class="card">
		<div class="card-header text-white bg-dark">
			<select id="load_file_btn" class="float-right selectpicker" title="Select file" data-theme="{content[@id='main']/@name}">
				<optgroup label="Subthemes">
					<xsl:for-each select="content[@id='main']/subtheme">
						<option value="{.}"><xsl:value-of select="@name"/></option>
					</xsl:for-each>
				</optgroup>
				<optgroup label="CSS">
					<xsl:for-each select="content[@id='main']/css">
						<option value="{.}"><xsl:value-of select="."/></option>
					</xsl:for-each>
				</optgroup>
				<optgroup label="JavaScript">
					<xsl:for-each select="content[@id='main']/js">
						<option value="{.}"><xsl:value-of select="."/></option>
					</xsl:for-each>
				</optgroup>
				<optgroup label="XSL">
					<xsl:for-each select="content[@id='main']/xsl">
						<option value="{.}"><xsl:value-of select="."/></option>
					</xsl:for-each>
				</optgroup>
			</select>
			<h4 id="file_name" class="card-title mb-0">&amp;nbsp;</h4>
		</div>
		<div class="card-body p-0">
			<div class="editor-container" id="editor_container">
			</div>
		</div>
	</div>
</div>
</xsl:template>

<xsl:template name="file-tab">
<xsl:param name="btn-text">Select File</xsl:param>
<xsl:param name="type"/>
<xsl:param name="active"/>
<xsl:element name="div">
	<xsl:attribute name="class">tab-pane fade<xsl:if test="$active"> show active</xsl:if></xsl:attribute>
	<xsl:attribute name="id">edit_<xsl:value-of select="$type"/></xsl:attribute>
	<xsl:attribute name="role">tabpanel</xsl:attribute>
	<xsl:attribute name="aria-labelledby">edit_<xsl:value-of select="$type"/>_tab</xsl:attribute>
	<div class="form-group form-inline justify-content-between">
		<h4 class="file-name">&amp;nbsp;</h4>
		<div class="dropdown">
			<button class="btn btn-secondary dropdown-toggle" type="button" id="{$type}MenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><xsl:value-of select="$btn-text"/></button>
			<div class="dropdown-menu" aria-labelledby="{$type}MenuButton">
				<xsl:for-each select="content[@id='main']/*[name()=$type]">
					<a class="dropdown-item" href="#"><xsl:value-of select="."/></a>
				</xsl:for-each>
			</div>
		</div>
	</div>
	<div class="editor-container" id="{$type}Container">
	</div>
</xsl:element>
</xsl:template>

</xsl:stylesheet>
