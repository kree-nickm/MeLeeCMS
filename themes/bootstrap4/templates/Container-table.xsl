<?xml version="1.0"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html"/>

<xsl:template match="content[@class='Container' and @subtheme='table']" priority="1">
<xsl:if test="title!=''"><h3><xsl:value-of select="title"/></h3></xsl:if>
<xsl:if test="content[@id='subtitle']"><h6><xsl:value-of select="content[@id='subtitle']"/></h6></xsl:if>
<table class="table">
   <thead>
      <xsl:for-each select="content[@class='Container' and @type='header']">
         <tr>
            <xsl:for-each select="content">
               <th><xsl:apply-templates select="."/></th>
            </xsl:for-each>
         </tr>
      </xsl:for-each>
   </thead>
   <tbody>
      <xsl:for-each select="content[@class='Container' and @type='body']">
         <tr>
            <xsl:for-each select="content">
               <td><xsl:apply-templates select="."/></td>
            </xsl:for-each>
         </tr>
      </xsl:for-each>
   </tbody>
   <tfoot>
      <xsl:for-each select="content[@class='Container' and @type='footer']">
         <tr>
            <xsl:for-each select="content">
               <th><xsl:apply-templates select="."/></th>
            </xsl:for-each>
         </tr>
      </xsl:for-each>
   </tfoot>
</table>
</xsl:template>

</xsl:stylesheet>
