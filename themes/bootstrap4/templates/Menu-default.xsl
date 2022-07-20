<?xml version="1.0"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html"/>

<xsl:template match="content[@class='Menu' and @root]">
<ul class="navbar-nav mr-auto">
   <xsl:for-each select="content[@class='Menu' or @class='Link']">
      <xsl:element name="li">
         <xsl:attribute name="class">nav-item<xsl:if test="@class='Menu'"> dropdown</xsl:if><xsl:if test="@active"> active</xsl:if></xsl:attribute>
         <xsl:apply-templates select=".">
            <xsl:with-param name="link-class" select="'nav-link'"/>
            <xsl:with-param name="id" select="../@id"/>
         </xsl:apply-templates>
      </xsl:element>
   </xsl:for-each>
</ul>
</xsl:template>

<xsl:template match="content[@class='Menu' and not(@root)]">
<xsl:param name="link-class" select="'dropdown-item'"/>
<xsl:param name="id"/>
<xsl:element name="a">
   <xsl:attribute name="class"><xsl:value-of select="$link-class"/> dropdown-toggle</xsl:attribute>
   <xsl:attribute name="href">#</xsl:attribute>
   <xsl:attribute name="role">button</xsl:attribute>
   <xsl:attribute name="data-toggle">dropdown</xsl:attribute>
   <xsl:attribute name="aria-haspopup">true</xsl:attribute>
   <xsl:attribute name="aria-expanded">false</xsl:attribute>
   <xsl:attribute name="id">dd<xsl:value-of select="$id"/><xsl:value-of select="@id"/></xsl:attribute>
   <xsl:value-of select="title"/>
   <xsl:if test="badge"><span class="badge badge-secondary ml-1"><xsl:value-of select="badge"/></span></xsl:if>
   <xsl:if test="@active!=''"><span class="sr-only">(current)</span></xsl:if>
</xsl:element>
<div class="dropdown-menu dropright" aria-labelledby="dd{$id}{@id}">
   <xsl:apply-templates select="content[@class='Menu' or @class='Link']">
      <xsl:with-param name="id"><xsl:value-of select="$id"/><xsl:value-of select="@id"/></xsl:with-param>
   </xsl:apply-templates>
</div>
</xsl:template>

<xsl:template match="content[@class='Link']">
<xsl:param name="link-class" select="'dropdown-item'"/>
<xsl:element name="a">
   <xsl:attribute name="class"><xsl:value-of select="$link-class"/><xsl:if test="@active!=''"> active</xsl:if></xsl:attribute>
   <xsl:attribute name="href"><xsl:value-of select="url"/></xsl:attribute>
   <xsl:value-of select="text"/>
   <xsl:if test="badge"><span class="badge badge-primary ml-1"><xsl:value-of select="badge"/></span></xsl:if>
   <xsl:if test="@active!=''"><span class="sr-only">(current)</span></xsl:if>
</xsl:element>
</xsl:template>

</xsl:stylesheet>
