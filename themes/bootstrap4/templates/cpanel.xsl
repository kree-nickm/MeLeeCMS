<?xml version="1.0"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html"/>

<xsl:template match="content[@id='cpanel-setting' and @type='select']" priority="1">
<div class="input-group js-setting">
   <xsl:element name="select">
      <xsl:attribute name="class">form-control selectpicker js-value</xsl:attribute>
      <xsl:attribute name="data-saved"><xsl:value-of select="value"/></xsl:attribute>
      <xsl:attribute name="data-default"><xsl:value-of select="default"/></xsl:attribute>
      <xsl:attribute name="name"><xsl:value-of select="name"/></xsl:attribute>
      <xsl:for-each select="option">
         <xsl:element name="option">
            <xsl:if test="@value">
               <xsl:attribute name="value"><xsl:value-of select="@value"/></xsl:attribute>
               <xsl:if test="../value=@value"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>
            </xsl:if>
            <xsl:if test="not(@value)">
               <xsl:attribute name="value"><xsl:value-of select="."/></xsl:attribute>
               <xsl:if test="../value=."><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>
            </xsl:if>
            <xsl:value-of select="."/>
         </xsl:element>
      </xsl:for-each>
   </xsl:element>
   <div class="input-group-append">
      <button class="btn btn-outline-success js-save" type="button" title="Save Setting"><i class="fas fa-save"></i></button>
      <button class="btn btn-outline-secondary js-reset" type="button" title="Reset to Default"><i class="fas fa-undo"></i></button>
   </div>
</div>
</xsl:template>

<xsl:template match="content[@id='cpanel-setting' and @type='input-text']" priority="1">
<div class="input-group js-setting">
   <xsl:element name="input">
      <xsl:attribute name="class">form-control js-value</xsl:attribute>
      <xsl:attribute name="type">text</xsl:attribute>
      <xsl:attribute name="value"><xsl:value-of select="value"/></xsl:attribute>
      <xsl:attribute name="data-saved"><xsl:value-of select="value"/></xsl:attribute>
      <xsl:attribute name="data-default"><xsl:value-of select="default"/></xsl:attribute>
      <xsl:attribute name="name"><xsl:value-of select="name"/></xsl:attribute>
   </xsl:element>
   <div class="input-group-append">
      <button class="btn btn-outline-success js-save" type="button" title="Save Setting"><i class="fas fa-save"></i></button>
      <button class="btn btn-outline-secondary js-reset" type="button" title="Reset to Default"><i class="fas fa-undo"></i></button>
   </div>
</div>
</xsl:template>

<xsl:template match="content[@id='cpanel-setting' and @type='input-check']" priority="1">
<xsl:element name="input">
	<xsl:attribute name="class"></xsl:attribute>
	<xsl:attribute name="type">checkbox</xsl:attribute>
	<xsl:attribute name="value">1</xsl:attribute>
	<xsl:if test="checked"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if>
</xsl:element>
</xsl:template>

<xsl:template match="content[@id='cpanel-linked-files']" priority="1">
<xsl:for-each select="*">
   <xsl:element name="div">
      <xsl:attribute name="class">small<xsl:if test="substring(.,string-length(.)-3)='.php'"> font-weight-bold</xsl:if></xsl:attribute>
      <xsl:value-of select="."/>
   </xsl:element>
</xsl:for-each>
</xsl:template>

<xsl:template match="content[@id='cpanel-page-buttons']" priority="1">
<div class="btn-group">
   <xsl:if test="view">
      <a class="btn btn-info btn-sm" href="{view/url}" target="_blank"><i class="fas fa-eye"></i></a>
   </xsl:if>
   <xsl:if test="edit">
      <a class="btn btn-primary btn-sm" href="{edit/url}"><i class="fas fa-edit"></i></a>
   </xsl:if>
   <xsl:if test="reset">
      <button class="btn btn-danger btn-sm"><i class="fas fa-undo"></i></button>
   </xsl:if>
   <xsl:if test="delete">
      <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
   </xsl:if>
</div>
</xsl:template>

<xsl:template match="content[@id='cpanel-new']" priority="1">
<xsl:if test=".='1'"><i class="fas fa-check"></i></xsl:if>
</xsl:template>

<xsl:template match="content[@id='cpanel-changes']" priority="1">
<button class="btn btn-info btn-sm" data-toggle="modal" data-target="#changes_modal{../@index}"><i class="fas fa-info"></i></button>
<div class="modal fade" id="changes_modal{../@index}" tabindex="-1" role="dialog" aria-labelledby="changes_modal{../@index}_label" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content border-dark">
			<div class="modal-header text-white bg-dark">
				<h3 class="modal-title" id="changes_modal{../@index}_label">Change Specifics</h3>
				<button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
					<span class="fas fa-times" aria-hidden="true"></span>
				</button>
			</div>
			<div class="modal-body">
			<xsl:for-each select="content[@id='data']/new">
			<xsl:variable name="index" select="@index"></xsl:variable>
			<xsl:choose>
			<xsl:when test="../previous[@index=$index]">
				<div class="card mb-2">
					<h4 class="card-header text-white bg-secondary">
						Updated Row (Index: <xsl:value-of select="@index"/>)
					</h4>
					<div class="card-body p-0">
						<div class="table-responsive"><table class="table table-hover m-0">
							<thead class="thead-light">
								<tr>
									<th scope="col">Column</th>
									<th scope="col">Data</th>
									<th scope="col">Old</th>
								</tr>
							</thead>
							<tbody>
								<xsl:for-each select="*">
								<xsl:variable name="name" select="name()"></xsl:variable>
								<tr>
									<td class="h6" scope="row"><xsl:value-of select="$name"/></td>
									<td><xsl:value-of select="."/></td>
									<td><xsl:value-of select="../../previous[@index=$index]/*[name()=$name]"/></td>
								</tr>
								</xsl:for-each>
							</tbody>
						</table></div>
					</div>
				</div>
			</xsl:when>
			<xsl:otherwise>
				<div class="card mb-2">
					<h4 class="card-header text-white bg-secondary">
						Inserted Row (Index: <xsl:value-of select="@index"/>)
					</h4>
					<div class="card-body p-0">
						<div class="table-responsive"><table class="table table-hover m-0">
							<thead class="thead-light">
								<tr>
									<th scope="col">Column</th>
									<th scope="col">Data</th>
								</tr>
							</thead>
							<tbody>
								<xsl:for-each select="*">
								<tr>
									<td class="h6" scope="row"><xsl:value-of select="name()"/></td>
									<td><xsl:value-of select="."/></td>
								</tr>
								</xsl:for-each>
							</tbody>
						</table></div>
					</div>
				</div>
			</xsl:otherwise>
			</xsl:choose>
			</xsl:for-each>
			<xsl:for-each select="content[@id='data']/previous">
			<xsl:variable name="index" select="@index"></xsl:variable>
			<xsl:if test="not(../new[@index=$index])">
				<div class="card mb-2">
					<h4 class="card-header text-white bg-secondary">
						Deleted Row
					</h4>
					<div class="card-body p-0">
						<div class="table-responsive"><table class="table table-hover m-0">
							<thead class="thead-light">
								<tr>
									<th scope="col">Column</th>
									<th scope="col">Data</th>
								</tr>
							</thead>
							<tbody>
								<xsl:for-each select="*">
								<tr>
									<td class="h6" scope="row"><xsl:value-of select="name()"/></td>
									<td><xsl:value-of select="."/></td>
								</tr>
								</xsl:for-each>
							</tbody>
						</table></div>
					</div>
				</div>
			</xsl:if>
			</xsl:for-each>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>
</xsl:template>

</xsl:stylesheet>
