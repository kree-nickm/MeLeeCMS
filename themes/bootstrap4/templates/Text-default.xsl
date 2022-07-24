<?xml version="1.0"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html"/>

<xsl:template match="content[@class='Text']">
<xsl:if test="not(*)">
	<p><xsl:value-of select="."/></p>
</xsl:if>
<xsl:if test="*">
	<p>
      <xsl:for-each select="*">
         <xsl:value-of select="."/>
         <xsl:if test="position()!=last()"><br/></xsl:if>
      </xsl:for-each>
   </p>
</xsl:if>
</xsl:template>

<xsl:template match="content[@class='Text' and @raw]">
<xsl:value-of select="."/>
</xsl:template>

<xsl:template match="content[@class='Text' and @type='select']">
<xsl:param name="id"/>
<xsl:element name="select">
	<xsl:attribute name="class">form-control selectpicker</xsl:attribute>
	<xsl:attribute name="id"><xsl:value-of select="$id"/></xsl:attribute>
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
</xsl:template>

<xsl:template match="content[@class='Text' and @type='input-text']">
<xsl:param name="id"/>
<xsl:element name="input">
	<xsl:attribute name="class">form-control</xsl:attribute>
	<xsl:attribute name="id"><xsl:value-of select="$id"/></xsl:attribute>
	<xsl:attribute name="type">text</xsl:attribute>
	<xsl:attribute name="value"><xsl:value-of select="value"/></xsl:attribute>
	<xsl:if test="size"><xsl:attribute name="size"><xsl:value-of select="size"/></xsl:attribute></xsl:if>
</xsl:element>
</xsl:template>

<xsl:template match="content[@class='Text' and @type='input-check']">
<xsl:param name="id"/>
<xsl:element name="input">
	<xsl:attribute name="class"></xsl:attribute>
	<xsl:attribute name="id"><xsl:value-of select="$id"/></xsl:attribute>
	<xsl:attribute name="type">checkbox</xsl:attribute>
	<xsl:attribute name="value">1</xsl:attribute>
	<xsl:if test="checked"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if>
</xsl:element>
</xsl:template>

<xsl:template match="content[@class='Text' and @type='submit']">
<xsl:element name="button">
	<xsl:attribute name="class">form-control btn btn-success</xsl:attribute>
	<xsl:attribute name="type">submit</xsl:attribute>
	<xsl:value-of select="text"/>
</xsl:element>
</xsl:template>

<xsl:template match="content[@class='Text' and @type='link']">
<xsl:element name="a">
	<xsl:attribute name="class"><xsl:value-of select="@css-class"/></xsl:attribute>
	<xsl:attribute name="href"><xsl:value-of select="url"/></xsl:attribute>
	<xsl:value-of select="text"/>
</xsl:element>
</xsl:template>

<xsl:template match="content[@class='Text' and @alert]">
<div class="alert alert-{@alert}" role="alert">
	<xsl:value-of select="."/>
</div>
</xsl:template>

<xsl:template match="content[@class='Text' and @type='pagination']">
<nav aria-label="Changes page navigation">
   <ul class="pagination my-2 justify-content-center">
      <xsl:for-each select="page">
         <xsl:element name="li">
            <xsl:attribute name="class">page-item<xsl:if test="@disabled"> disabled</xsl:if><xsl:if test="@current"> active</xsl:if></xsl:attribute>
            <a class="page-link" href="?p={@number}"><xsl:value-of select="."/></a>
         </xsl:element>
      </xsl:for-each>
   </ul>
</nav>
</xsl:template>

</xsl:stylesheet>
