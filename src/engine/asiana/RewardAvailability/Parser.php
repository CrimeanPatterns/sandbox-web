<?php

namespace AwardWallet\Engine\asiana\RewardAvailability;

use AwardWallet\Engine\ProxyList;

class Parser extends \TAccountChecker
{
    use \SeleniumCheckerHelper;
    use \PriceTools;
    use ProxyList;

    public $isRewardAvailability = true;
    private $depData;
    private $arrData;
    private $index;
    private $abck = [
        "68E1D2A8EA2DB25A2292D7C96711E912~-1~YAAQCAk+F8tf0SqJAQAAVR7HLwqvgz3tTS39gZiTnadnIOUYrMAwd7eE89DSU1X6Vyv4eYwrG+nhDHWG4mRoo6pcaz3CA2G49ATwxcBt/jyZ+nrWUfexeOt59NFrNYSobLy09qoD9pZ1hVRlKIhmhGT+SE833SQZE59Xp1V+qgln0hVQfVp41xPhcziFUhnkvaeeURN8FELv+YfApaFESuxx72Tcm2ABMrdbTdmyySvw7zSPBfL5BRFyAb7oEvaXpAbcZiPiMvrb/WrJQbCC9orK2K8GsW3suSGlRGPS5mqGFhpXub/rPUQOeAeRh2wGj9ujiaMGHG/B1DlGQqoFDYFUjcVfBUQ9wlh5+PUVyS632dR/SYGj9SpRSOzk++puu1w690Z2BnCwZPLOKscIaJnESlqB+vXyABBwBnXhBWN+DrSis8xofQ==~-1~-1~-1",
        "7FE41BCA0E21BA604848164FCA651AF7~-1~YAAQCAk+F7qe0SqJAQAARn/OLwph5im07tSKXARblPZ4gw5rUpy/VLG11YDuBaLp2v91q6clCWPaf0DPARjI/rwfhcn++GtYNp7AKE8pnZ632reEmNUU9GXSkAKjZbnMbiUCG89NOkqfXl751LRM7WBYTGrka8nO3nw9qM5zwmSVHM4gZc5xdl76rGjWDn7kpifAY8Os6sEAfvdk2dFUeDRjRmW+Yh2tIV52KXYaLUufD2W5RjLTDxS+AVp+HApluhTcpDSqWu0B+3y9ojYeJrg9IeOdBKdlwt3rddJBO/QBr7TEl+E5QDIBIiDd0PBAAnODSkhm0WSa9/i3ZviKNW14zvCwTIfDsJEmVAL1vPFNQ1CWwHPxnGTybkSMtGSK3JwZAffg3Ccbmt3qWwwVa8RW5YkiItZHeF8kbjBMH+t3Etom4tkv2sI=~-1~-1~-1",
        "6A6A520CDFF4B27F7267E22FFE2C6D60~-1~YAAQJDIQYO9mxwSJAQAAD+bWLwrieM0Y4I02jfnUf8+58/zckkHTB+HYkJhMZxjlmYWVZ4N2odfSvZVo3aDHz9MJTNqolmZ4BmkMUVx0SPO6FymSHAd5beOlzRg5kmeqaecr0oPxRs1qAipwSU1BKe5fSh0cdzXENa1y8NekUI+3TPq0yEW9OEpw9CXPMlczy5pys+HAVGQTIDnYO1YsZp1OTadS5mP0GwrhqwSrIwTynqyfVP8mlDesyTDBDargzmpc7rjipWoVikUdpQUXMEybb6kPJsZrg2R8cNj/REpsYs579NkDTpRIwKZ5MjBB4aeeHU6cANREZkXph/37k62rUa7Xv9SZWs0Pmo1x7/BrxW2rrvdjDgSgC1GDDDRhRi9al08qSkP2JrYyF2YjYEienNQaydqeJumz3SdrDJ08KHV0ZeslzoU=~-1~-1~-1",
    ];
    private $sensorData = [
        '2;4273970;4600899;27,0,0,0,5,0;X!eZin9P:H H`x+rd}:#$w$D:_m?*A,h4SM2RQ9N4R*P|<G.*I9jgog=@t!6PR@.L$xj/:Zi(M&^3CaKH!@=$BTv5[k;OZLJ}E=0[|S}e<)Tz9peaDisas$Np=_}=K35XdTQc,!vipj2x=ecbM{,v>O!E&? -UP?,_jx:_Pq PGvd&XUqA&513mFdO$)q37<!]jxV g=5uRUJl9C_I/1v0R@`hB$>pm3oYetLumTJ5?}j%[YQ/f .g.mJ#dHGEC_1.HsDtU]^XE-<gRgqq_S2YyrP9hQ$Zi.xz(=1a`M]]XRH{e~JE*Te}?q>-wg<o DD7>aCSTAML_GM;}DgkV,FyTH6(ilD+7j^;5J;&4-21O@k8*U|!xUtI_e hMR+|;rtqCaaCp`t(4><0(S6,8e sXQA_C@zh=ct /#}m&4|YygXTO)/,>qF:{ kzmraT-dnkP)*&;Er[DNdm^:t$;eE8$C4{CH)=_P_[G3-4@^(0Vm53:k`k(_FF9;EXeuVc?z7M2u0&Jl?R|1XYhC0bqN+>:OHfp`wzTA_4pXr}$g:F6Jwt!u]+=&u% 5Wjq2,M*p,JD5R^_K!?.Ew&@60L}dPZb(*Q[-@< (FrjZo3i7t0 3t&I6Cot7t02kpBM%{;0$Z|&0!b3^J}k0fyA;HkL9KYi>H(BBz1,-a06=b`i1oFAbR|:7QyKNNj#Vp^c;K0{&P1;<f,hP7 m?3M#~,iJUT):f<o@k}HW`4!01f> a_+-nW%oow_7@_}B}F2H1I(*6ydu*h1/fdIU5qI~G^Gx1Di2yD7bJoEHjQ@fhG%ql^Y^<)nBh!lYn!e0r@8pOxNw.}h&@#fcZUBT>BXg|8M2Ge.na5*_Y/l&:82WtU7?.D3=?G^g8UTBq/o<6!{/K(X!RC:t;-tpn)T9%&!rrf]S2I>ihjO=7nM|r}.|D389:OLo4tdq5;idS>/%gvMwfN7nA`|jJxp}r|(mo)=$A8`jN03LU5E;| v=|&2x@P8`g/DXf)~|T(^O0bZDUEYHbLKn[`(f@?@rvAUv!PwO(jy6%MR?NKe^~ue>x9X1/--^b1D|<YdbJ6VlL)<@PPeb&v)fDr+w^lk8R0N|Chh~iexB*gvy5>O|,,4ec#D9}AKx?t2|-^~-({/_UBTL+{ITpv+sf*_U$mtFrG,g{as6 rDohOg#BF*+_b~d`3j]Zp2v6yL8b7PujoIxqg,6ipKf=H]Sh0Wc`&G(cFqt3y=YZZ>qzy4Fu8u>_ 9<9rLZW+W4kR9/TAa7@C!kco1G(Q,O,?otsJ*@DyC2d`FHvBpA%(`F[Z5H%R9~=f1-?#o(-l91oaOE6 J#BVy1:DlD|F;rLt8Sq+BXkJ#tUdUN4,ZRY%pOr}X!o*.aJ|MRxsez=pRLAB.D,$mMm<_|8I X8-oU<dLf4|qAY?-z}&$2z{iKr,8#NiE~ 1S-&]@W0sxHkS<GBSJfRlVxh803c(j@?!bsh<(EtvkLriVkci~6>A.#P~-,i1oQX<I.$vp&TweBqg]is:8W_}b^0hy E jJYP_ON36[-DH*TdZbk:Atu9,OA,*?v:]TZXVZ#/}]F^hMtO5sL .O!_zz 3F3JEKH|Nn5tbYNj~-q@YUb<xN{1YWhK|D$hKLE2?_;uqiN/[gcKVc.Dx,y(RXeSYn!vPZhpy;HclfTE]9|m~%U%a}SB6+FaWsE(z2+Qbd<IJ_D:o,|v<T *1X0Q0<VjM#O63N!q}Dc++UA*d56&I].[Z{a+dvGC@m??7FL7K{1Xmx&}/)|z>c?v`@#J;^y~ d21#Oq.R&P-)TK^akms?fUynRB#b|BWR6}r/E[9K5c9Pvp2cIXS|R8(tVQuv;o)!hOca$D1L0fG`YkhUB|vHa/a`BH {@}5H4%cEj0{<0e@k<PT66Kf7mcCgD>,{N<-aO2XjGnU,yR=b9OcG>qsVBDe+t uoN2Iug_w}r7,fJ0nV#CgPLsp%bTMYM]RS7wGh_]$5tT8%#a] f,JFKx3>mwq{%4b4x_#aON|G!W_EI1|jKq8:4n00!!#7?Y1fL?nEAOo< ogRcCD7|9u *_$7v,%8*O<$of>z-3f#!bm[n_YOAnE@[.hdbr~GVot7-.jPIfL fkbekw!Pf=Zv9iIP<uvINv!E@S,m#~gF2iQYyey9YJ$mr+7X5Nt0O.;LXg X#/&zY4L(M#$,xsVj:^TcjS<mf(rcNW==p+P[D[B?z`j%5Eoy9iV$u/0F5eM_]ms$Xy_[SZ0G<KS3x{I#)H/D=rB)[6;;e*4(+%.qJ;G0t**<$^0GQ)n1_q7AvZ[I;~m`1]$p7,EfX)uE-umE{7p*8/MVJ=Pk@b~q$k{u0Qn5fyNVdpxWWpJ%r1X2aaVAnOW$wV~9Ust-k}m)Xc><$T;EfQF$MXE$s{C$CX90Dz0y] gsQ<~Y0jN@<9 ^P@ZMRt>;1JD}?[&GNlAd=1zb/I|`|-W5[@2 KJ<uYX0,8$6E-_?X*9Q1+@nu&/79arlP>8Nlb4mq7J-^{RiAvVR~r6,m}NE;P.F_}p#{dsCG+Yh-L-z|*bY|I[{_IKlhQ%b)LH~V/d*-,/-jEWt:0-upC*,gcp693@)wfCuPGM!72}meeT]>#H9ZSFA(pz0X2|m~r%H}25c8+Di]i`lD7c;Be9opLl~>Lfx7+)Y>`V3jiLaB_aAPCsXmJT-.BRa/8po74(qSmzX-1|,&;9ELB*Ze,r[fR22gxG^)[0rSp/#VLQfl~z?3oxar0seq|9W/W)9|z&rT}:{qow&$IOrwwpSt;tm~,R1X!_sFQhLYl6$h{yKW}-<_TC=S mn$Gm=w3XiC[8B:82&a&~DAc =d3F:wEZH)eUtU#5T=OtoeAj^G;R}/u$$+@F:iF6@k*g*sEV<%Ny74pDJAfz@]/W+B^Pc<z~yJqP$deEwdyLOe=fUb$3lO+I^|S5T.^~ntsiCSN(-u#i&i0tsxN~h?I>|5acyT:M_Bf]59)sXPkRkv`CX]4V@oN~ofXgmbPAwV*)n^_TA:`=Z~hQkrHz]1,5Ab5_sgNZ/XM:3,v}op@ EecW_!V&qMH$d;!8ZL-T,dK>ED?J94n`#IH6m#[(w_QCCe;|40(<lZGM8PCkUdD([%!}HaG~{_gYJbt5K]c$N]6<7EPr`hv_&PQX/QBot[mRY@~H$X/b!yx#DOZja.mxAh}$X+r`g^WJL37fj8?|RMHdZ36Ucr~t0/6A8[S29-H2T//d2PYAunm2Dc!KRo}pH?IY=lubtlv-9~*RMHhR*;0Ws4T!pVUXJ5Un@~(~4+[UAahC6EcK*PEy/QS26myMD&GkE29sVStz6>E#q;QN.JS By:p-ge{lh1IB46:Ze:7_^_UIwC+~?IwH# 7Tw7TSN`5&q+9Wf;Ttw+.vKuiZ%@u@%]8a6Kzou8}i^~~Cd9Z ,F.7l7EIg~V-{D6l;`.+.f3',
        '2;3158326;4337974;22,0,0,0,2,0;6-J&,&i**D`Vv1ci*trBt|0P~~R`T[WM22a*4BFZOUd9+<$9WpG?Q;;vG@,J[ ,UNQl2H~dDbFSlZnP`7fh3z4E-ZZQcEipukV-gZrmCjr;M}*Yy;5G(eJ;!`yYfs.0K87!^#6(/srLg9G%7dt$bUe4zTdgx-FH@AoF4X*fRonD^>b9Q},F60RygETV|8Piwo+tr8TK8Oj<Z:cBh4UmqW|UE2[Pe~Ul+0J4-Q{ r9?wT3_EpK]!q$M%o_&-evDR51Z9>oADFc/Z/hP;uKb<2-e>7UTY:R*E-$H?G+V^c9Q/^Q4/O7l-86vSoUl*u0f#Uv98Eu`bm@oQ?:i8g%Hv&4xb^j4J#L+^]`~22{bopU}rVJ2:jQ#_y/uOglKjDr^!o/_k.kIdnY2.WhiU5.gHUq^B[w:aN nH-P+9O?;UlR#xtiW_OO e~A>&EY3gV xhEnIo*|l)N2ruYsk&WeN)RILBd!%MJXbA*m9[-i>8fW^lY~+sv.ZwDgotUvQ4-qi[]!h5Y^P&oI+64XJiYk^FvMOQsJ%e^RZFG!r9imuAzwPhp41QNgaOqMn`FdN:C 9x)|f0dq}^*.}38]dH0C7<}^YuR$pYSOnTbJF,LYuU+4%FV 6Nc~}!cC`mAz}0um&~*&$^DMjP4+7Yh<Vp,ILf;+G[K{ r9(@U[CN0w9E/v70blDm=It,8wX(G0dwm))R`xttZLm%n_j(OrOl=S33L55UtsHS^w%E_~{5sX,OF?Nj3]7T7i3FuRNzHA&@>eu6Yy+1~yQwca}2n?7I; 5Or`h.5-4x|8^)Jk}>5!Q +#YiDo_*}Q2Izlq?vxt1@r-]*Od( $V-8:f~lH0tZ3o5z^fD<(|BT7S9d,DwklO%AKx1#UV8$iM&HQRB&)#Yj[yR(^zcPX@#.As:5s^HO0xC~3K~uz$`tO(rFwJny9 c{7$?1t|beHJqReuvLkwRud4uT@a<HYOBcyH(-u(heQI2n9D?*G`7cZ$}OHiGq{wg,A-qtXjej_hB/An][u>xI64kGIn^}D&%cxjMCDQMaaS:JO ~|<lD u^l82uglH<8l57g#7.1O_I7)N,+#Y~e?L0;0 _Kr=9H|EO3ABdc3$-)~V,+1oL dkU`YS<+U3BO0HVFl`!3Mfo^eEqhiBxuqgZZ$)mi;t/6mlZQpiAO $C7=zfz(h87z*/;9V99oXcx^OGHizz=&a+dcSALdM$,s4<YNV`e?y=wG!77_&>n5Uu *(u%D<onl;,OZlh~T9^vtXZUKh3`<Q+,D0#Pf[:[`x~<Jpkpb^wA9.EU(]}L0^!-SEBs?xt&-J`.7a^}cb,W[>czW>f,V@$.HJFwL44RV|<k)PMn`Es@ha!?vA1e)`4aGB?!WIEYx>Z/Y-:QIQ(hgd,L@hU)!a9X%<8{MNCk|]zT Oh25AkE`l,XK~yRH~h2`iza=CGo0c.w8i0zglH;HV*V?#ziQ:2Z(;Q4/-?l)h8+D%Nu;L%l*;xA;;w]hHGrU=aCfewQw`:wM:n2?]L@VtZ.y*R/NbL&kyD2,=U6TG#hQ<aE]jf`|<~aaQ[JXIM(,5XK``s_?$ZY63pCYw|s?bA1/!/&I];zp,^{`!$j/a29x:Q,V#y~Hgc]WDei| +k]1Voe;T7s}^pLICrCq6u={dWo ~0OMhG=#V`j5^c/&=jkZ=,PP_gBfl#UX%6_Vt`)EH|<Z=7Xqk6UcrJ>+6C,jtYhubd#BTMH &SdU)=2=:UCULLdmCl@%7?j~Mm3B_kuQ@ToSF0v/3IEi^X3 :X=w*5]|Va.ry{5gcC@#Xa[}|?JtRgM9Puea``5Q+Q/Hr}1|t:]A)5Dbp&5OXhJ=^,*q)7^.p7|-fq2#vIm_Rxn):k!3Ff_B`CF:Af,]MxKsJf!}{R $s+.S`N^xn<7##;9Ffs$l{:^x+fP+wZ(~*m8);DS}ZPWmO_HEs| RTue|.fJ3pmPe+HYy4<IV8v`4!Ipu/q/r5(pXN@*78,)pUczIxR/{6$KF}#ZjOyUca,c@3>pY8YBS<$T(p5kfsLo[WBMH,LC6;EE!kHO_s#uy{g|tF(,h~8pwFd+p{:qMC(rfS3P@aKultWI1HjrL_kB6+t;44RhxGtX=bL0HAf>g9P5=cl8^hY>8oop6;+62.6eF)_fmRmA{ @Jys*163le~2(3om4Id_{@]M@tfgfS+9K{NGb6?_Z9g~~h eXA>dR.}]z#NDt,M9tcTWwHev,Lz-]u:Q[hjY3AWQ2rni:3P&+GK40]8_F0D+sCS9t<7?)MV~Yn_U:Ta/aO0j$jq*^Cyi7Uw^(>^NnZs9Kg,[1OiN2:P6ju,^.W.V[<GR^XyPRe5tPUAUK]LU5cp`.85:Q!m4RA.GNx=e(l,WV.cRv7#qxs@Hni--J0|<[VQWoZcAeCi<K|[Ow8.*6=]k7Nzo)2c)aNS6,M (:p>{#FTHs=6&G]m.]yH?WSS IPQ|,Z9#S=Y;Z!#-X=+&CQv9f3]j5.|d6ht/XD0#`I h MWjAzxq0J|E{E 0SFS5q} Jr~:)ne@tH!&.fGYa.6q<}Sw>!myT@qT![K/}YX9}Q87YA|1:^!n1w=!=kr2jUt$n&!cM;%?%]?RjOAOW=P9j{&p@5*6xs1A)bXP?793&Z/ItrJl.N$k]2p]7ak<)&<W;z|ayhoo[60IgYOz4sL/isLCs@`}#vBVO20,9(K@M%n;]lL~HyVO(+j#;zH{cp:`K<:INMmlrLN-@BBl*}a^FN.}mNuiOAU>uuD;W`{2%sqDXjSj(x]OJE?Q)s=s!3b4-}28a`)7G=<fB^f<adRGEQ@F@9]+;r:gob}%Mc./TKT%_{5z8=.38FD9;AvO`cW3>Iaw>U|(QH]7t2o*fk`}u&$T46mMf.nX`g.P}9pwAMR?#?fJ.1(>Fel,y$^N82tf71JhHd@_%:O56@lYLJD_U2DoXlJC1+y,_tIoe/y/Wh1$v`ePDrVw,ez$9L73Vz{h3OzAxU',
        '2;4534327;3425840;33,0,0,1,7,0;aPjh2>w/EMj`Vy?BIh}Lxl,oCu3{TVzKXEPZ=Q!5co.}yV~%,MOC|_ADZ0L|~eQ)-(@W}F%:d|B45sRY_}K4[S8!Q9_4->pb!a{,Khs:a.PHV9fy.U!n?DtvJBm3AV5o-,JzYoZ&[}L]bC*CH E@+~_RE8UDLZN-)Cq:tG2tEbA?eM<F`9geoG8;}9I-:[B1LW<Z@u]6,Q$4MH(JjR&~dtUzVD8z+O76XSHiL1#u]s_y.%zClL#|}/&}NRHMe(&s@X6yfJF|rE+ImeZ2QME-y,?g8;*zAojm-P=xkNJ?1^Zx6D>^<)r|/B&NVWE?k6CH`A!_bH=E9sk<ZTbG}u`nt%1OQfST~[cwDfQCj:;)I}fDqz,[==!AYpsHLfT`6f%%Z@-}v{))-uq>o.5y^bX{PUW~7NSX8w5H(KSISTfmzf]3u6O(h!|FH%8^xF>N1t2N6]a_l|29REM+i6Q5w*4LfA^=Tq):rg5@12%s+0Sb?y~iMn-&t,+YsV$_mAcndCy?%g~|Lnw 3HZE)J rUx4V}i.r7[#@(0yxh`}{AEBnI<J@% SJMSupTBp_1X#3X?E7:y6SSsO%y8 JjLxS|K3JcKEY.kw4>?e[lxFF(1l.`p+|S(``plC JAwdD(W:~!ZNDMj-W}O/^p64HvPrtH[J&~jL?#Ho!,zVYg<@1T4Z<T|1`}fp3a0kOG6<kV1hq1s`m~>wU%f;A:po,qZ{%`>+v}ihG?oLf]O-w}T g@-dRfDWe^Pb%mL4~7-zxp;$F`RIfzT5lOE&g6!6Q58V|[:D041B0.qV~D:8|yIt}vR*tO:Y|hi8TN8,/::EFA-tJttj.Z6mpHT&<$Qn6CDY8 i9P_r/>]C8Q/(;bq{RJPF*-iL0]Bc<R[KDW{q78KLBs:SY#HA+1+{n{Zh.X]uLqwt,%RY$*@39sL]YDoaYeYeUy[9#eYzO0A1BU!9go}(5`0+M7^zh$~0%I5.^Sicc=E~@~G_:K^f TFVkO+.,5:Y[W2F{zKZH9Usr0a)[c+<R5z@SR7?-FYkyARBwa.F= 7Jq6p)j0Xj|gR7T,-3*Kct~COYUET()`5De%g1p<{|>/&burZnm9D~5`S#`^zNPHw#D!c% Bu@Z@Q$:E;N])Cm+[etxx1r|fGO2[M-Z=t)P_UAiTc,-oGdpHj9u|G+63[ri6z[K|m+Xlx8/&yCiDN>[$Fx]tU3>Ei=$b`77]]DSVhVU75X|]0d-y3PiuA0lY#O9 |lP<2T<MO?~6Gp(x>(W=H->N+ 7P}TB56%#ki{g~8/s(8k8,Y5v[#[vTr_#:!=N9@SnHcl8GR6ar}z:vgUywCn=P@7U*3CG)9<FE2|U&JB8LtYv!RF) V7Tuzn7OKK2**.OG;|wXr_X]~[>4%;1$eGny10Z$zRgkt.=35,CNut$QsZ=G{/phU?n62>w2<-64Td~{@%w]0(3l43jE>hOP=5Z4<J,!]I`f-7[[sriJ-53y>>.74:*=(vP$=G-Vd&{$EX 1^OQAo 6~RM.M?`QgNM)v%2wPolz6Z~CaN*.d]qwnKAE_Tqy4w.lB_BjDXn+6t5]!DI[I><ua/>0VQv&<BE-o;JA6IHw<q/x<Zc%ib}Na*/-RW!8IM]T>Wtzv{-h$k1g;d{9||MpiXas3?kvCRmOIu774ShAh[~]6e!L57p8)+5He$Xj>CZXW#X[Mz0t:aAy_[m2;<yS;AZmEQFSw7ZMG!bt.;X2zi)-^:V0(}`56A#+fd<x0R|o}K]6S%ig37~XLA=HceM2Pa+G$O*U2F!|5pUYlDd[B9*z`H~[co`>MY1L#cDfTvMGhT<Nd;cbCG$9|r8%7L7y;CzL2cL!m&d!0{f+V1RbJC;A(0QA_#df;5k,{MA(qM @FGIanp?J#@>A*/lIq>04LVG(_N=ywN$FrmQ$M*|{uz%.,<kb4TQK12yR?8,^a;zD}ps:b[BWEgYwi#{Y.WZX$J=t|Xf[N*yJnrzG}y0|o2>RBmYJ-bVnEcY=B-:,&dm6UsyC:*l::W5_]nr~mQYq<dgDRPNNU89qGS_<{(|7Hi*IJpk{V(P_,^aIc!uivcnB.8KVy r<X~0an0+?(gzE}U]Tcht~68:VTmyoyjk(P~5`vEP`Q(QvY;QH`Pu|;;`.j$QC2FGnAs:a%YitXbz*-x%*JDy)8IQ%$Sfner(ZDc!Y&Ym,rk5iL;OY{*Rw09EB/Y3v :+r#no>hMf#rlJpjcm-P`<Xv K(7^31+UxN%o2&`<^&s~H&/y6@!Ij|ey2{4S<43Y}_!WseF|8p#,Z*_j+wFZQ`<GU:-chui@AkqK7ySp>u^$/bu|EAhCx!q{.QibyGB|tL3(XP0-%{x`?||I^&un%0ncYp-qU5@~Y9Mduy48je>nUN>[q.Rp`Xl`=6hf*eHU2(wsnn^y=R61LMQ2=gVpisrVJ$sTdOB/}U/IR1b${s>M<m1wVHU$E5iO}q0GL4Q>74@SeLVXP[~`KCx(ckO-cu.FG+gh O^3UK(G~3 70SFKt[L^S5#^N,U<]?%,p)]W_~xJDYN3aN*bUs/bV&>+#gwG:<Rg6PdY~p7L|^L/k+ -+X8S`qtj*D!!9BpX8)9QiXn&Z,=:J)2Ir&]=P,L<yw+RvGhi CEg$bwO$f2{q=iu+HeK{OC?kOk=Z)$ ,,_wrQ}7ehS Ff{Q&!VR~[7buAp[RS@^YSSxnHYqf?fCw<0Wy#vq$50_3O^mm={*QH1Mm%W3V<Z6LkO1^{<|-0UtBFe!)%r6hOVj0b3`>rH(5KNHuJ;&NT{6g~1[ec`>ZcT 5y@5qIxy}@9AjUE7[q2W.Dn W;8 XL_->~fJ|gG+Cr{ pA,EWoGh,M:Nvy&J;;?~E1_RZ)e=uBYf>y%{U`BjUqKz88EpAX2]&~[Y?&ra-jzxxTVh2XFd9%Y}OMkJ;Y^:jVD@7Z}k=!YT@=B|v@P?(x;.*l*p]r;8u*3]TU2uMU|>N%6QD{pfav_ Vrv&q2L4kjeoopcW?vB0]Xj.yK0elg8gB]<~y>]XB=*:@P99*u7eeV L3WIE^(x?:{(snMr]L^K][mx{de(P[Z|G9vnWTLK#r?Nhf4t]MSU~t=6P;,cBBMqN@q*nom%G@g8OS$W]6dk~>WJyof@})&d2)xpv$zwhhypoy_?<=Ul{>Wj1y4jGRjKhq]m<}#!xL3`KQ+%xx5Pu3Zj=Q=uXy:k]THQdxoy.(TJbf_LMT{/axQz%1C.{8SJ()v@CL`xrrdKn.wbo3X`Lh>S(.@3{Ce^>?1eqH<YfbO@m]/|3Tv(vCt5j|3+}OiZRii,=mvB:I@7l( #HX(J@g>vLXzzlItgZ{s<U}B4$.z%J!t}<^3`K$m9w-WL]:WS^n(EoBg3@NRQ9`az__7mb1kU4V%&1mPCDdzFtGqQ**{|a=ONsO9fQ:8`O|XVVC$}~UrI`XtJc9;Q*Uv#[0~IN<+jcufexdRFa*O:TDoSyBEfI(!2cjQ25##^Z)ZyCx^,8Z}rk*V?wD@o^5ftF+&li]o`B{ev=(,>O K;xg3:^/JoWNm+sHV0I9368kXBH5!Mc(ya?4msBu0QDUm.tag^^bsnoCM/8%}SlPw|aP>.pV{I9:n7!YHJTM~GrZLzq]?oK7dcMlde50Fxi@&YL@Fg-6MOdKDzDUT3KJy&t~l~YEjPNR0diL_s/?UZkps*#$2m6y}tcvqoc',
    ];
    private $secondSensorData = [
        '2;4273970;4600899;85,39,0,0,4,0;[-iU^h@]}IzBkk(f^}:3yp}GFZt2-4!m0ZV>NX<Q)M6Xw/F:3P|eYJMYEkvl.83(L+!p&A~>wJ,P3DcK}s+9%FOz8cm/e<=$Txl3^zQi[SP&YzMFJsq]tWh$H.!SoAqvu$FV~s&Xr{c(+u>?=tJT2{0zC#Jg+R<;Fw,/Z^T}lN:vl%`QoP+,)ThCdL#Ej,@8xevkS$a=9&wP=m?DZB(1.^w`(l5Us;HZf%AO0~jNO8D j8x/N1fZ3h+aG1g;kGH_F8GeG#sW4#N12eTiqo_|&+q]A>tW&Sk:{r#D:m_7bU`K>(i$T@[CSCki?5x^5t&CC7d%cPOGMUbGL>yAgg[&CyKD+#B=>+qm^1axu*-(e9WKY9)OW!uTvV.[~hS)3v<jnzw<4L[.m$:i=* `2,d5*J0V{DA9zD?;tMRP}wV4DUIt*E#~-]u;I5}(?~wsk},/=r)^/ZCvr!CQ=[/5E.pq~j-r^s8E84WHd`=. 1MfvbNZ03-rlr(_F@2J?O^kZ^8z1J2u(}I_8J#1QQgG+[qZ35e<7];tZsUFZ%q_qp0a8T}Ako!xj3=+|.y5Jni(18+h KE9MPZ*RM$>u$D4)@tiCTZ 0M`)2= ,Lmj1R7d,n? 3!1H6?q7,q0dSZBV) @+#d!{(?[;bA}m<e,dUd&>5N5Fm+rRCH,,_Ix65Y$i*tQIhMv@ADsRZJo*ZqPd<P}rxK1;<g8tR7 mE;Y!},jNRV{6k;p;e~LR8+j06r8 TX:-_W(Yls_;FQ}<}L.M8V**fmNn0g9#g_@J/qD%:SBx1Di2$G;iJqJLkQB[iM unZMTA(mBe(kUs!d&jLDrOV4F3r_,C+[g]PB--#ud}<3_2I.ei-}dX5fz>.-Lo]9A&94=l2i[5UZJp.d96{nO@*e)N?Dr6-jte}q/#.}e5Z`Y0K>_lOM=9nN#CNHsG;#82LRjcqcZ0^cj4`HWy[tS8T5a9BP$JLAMNrni-48EuWWg>j];MAC5;Lg,{W$_@SE_`&:]q{z#`,C|xFZCZMYMaYTn2EP8w5DWE,:v!O=K~nu7~DLAZJ_1nXe>x9X1,.$_b:Q#7X[hS6[tL4<*GyRPwsz`Si#u[pr8U;Qx@]l l_}A-m#t1DQk!Uu@:+</)HNy2{;y)hy*&q8^U=JMsvAJqv|gd0`HwY[FyN)Y(_n6usEom[jy>M.7Xd)k`$~?6SH3%#E3T7OuozMtxe-;izWh9BbOY1Wkg.I#Z>qk,q3^f_:z$q7Ft4p17s(8of;RT(O7sRD+^Ih=;J/rcC)2(R(O+FrepO)ELyN.h[OP}j)lu.lZ]l0^1gNjLo-6S$q)3z;=rkVJ7;S)?>9+(r}J?^FJ@&?9DK_9,[:,cd/gOGH.q6-;Nj+R$VLERd)A1%e|7u+4*IdvSjIw*<iH{tmq#-,P[Q#zRN7c]T.cS85r$=~b-Iq #}Z8@tw04tpz[]U[rLRyvTg!Bgkm;zVdFh+IN:>OJrbku[rw,%g1D8a9znUBhWKE+94@5}Hfe1*ey6s *r9{@2!:B8:iq+v]`L,m,$`og~[:UuHP>5s+!%m?7ZT9$PeEoEDnGk/<_[-Cf~HEybH2rXUNg,|,D[_ gs72j,1-pC@DeARTvI=1eKNTC-8nbC.oz;mAw{_ySwTNN`Vyiz%-B/IxKl0?o3t[<.A_AI#)k(MiF~8-:Z<l(FR~NSwA#pO%][1JT{>q-s8rC$wLU;eA]Qk3;-dU}c]&:G]>FG,I4u+wV~4QYavU5*gew9dYzypsrpy1<py^32^!0iZ<C`)}]I$.[,Ozs{by))Kg.[/D%1aFP~efK2HHwrSItU#AVR6}rv<R>O+^.JvtucHUZ|O=ktUN|i8c!!lP]b$L8S2VG`W}#zb.0i/0``HP$#@%=T+{1>c4n9#_@k;IU6*IY1m`HL<6 |J/S^L=SjCfU%wK=g,NWK:i|c9=##m&loM(Nuj`x%_9(dW1yI~7aP/=h]5TMiLWLL;vCmZa~9mV>)xVW c1IBIs8(mwg z+~*vd,[TT!Oz0P0@.|dBk5C:h1)z$|@BSTZ{7W8DRo> mbQmCD(|9i}{X$#z+y>Loitse@*,%b)|fcShT_JBj;G`6fcPo+KT{c<)2lSIeLmhR4GM^oYQ&Yy(mLC?ir9Amz@?I!h|1kE=EDH_M<OI0 1?.mq}EU :kzEGF8!;Q@{st$$@|K;O1%AY@;A@vxX}Ro*9K91OfK0?8WRw6@8P*yAePD_^2L#O%C$.<0{Kl]KKQx(Uhz!4(v4aUQcoRnQxZ X`(ooBaeVqc]8_zIdvPJ{RQCq+X~c>+G(<ZSW*_gQmp@X5Nw .09pE3jT_R#uB?oRb588sAI;Rp9^-(}!WY_;kwzNbT)uG@x_ M^aGs|#CQ/W9[5FnsxO<RU)AqbPDF.l%ps{i?PX|C&Cq9a]wx>y[r!X!qSN=>uo;r*/|m}}Uze0wE-L (C$u:@QG0 6s!sf(s&E?#1>)O2_L/h<l%B5y;]Bugt3uDW6vWY&j,HK`T9 sXDyKh0tq5UM!,u$w,(x^N^42!DgDsh?K,}T#Oung3VG23,1>VGO3&GozeA^C&!SbI6vQxIQDpH?-66JTqmf^Z{RRP-B5xwiiUS7)Lr 4`(2t~2KOvfVfcD&49c0!aben[gH;m6CO6ofRk$;Gm!>~YQ(PS8hjIaIZ]hR<}WdrInan0T.Djo+.(nWnp^87~&-?9JB=|)]kkYg/}`bw b~T,q+q]|$P~6h!R:+2t5L2Oj{$B-2V*E;uSC0W<W>px )}&Hy$]#C;yi*/^tOtYsFQiPUr*$p{&AW~*CDPB@Y{no$1s4m;v:h!D@4T($a&~R_*@csyta=ljVo-m:~O9O>KvrgBleQg;(Bh({H61b~T,NLRCfN|$VVt:7<vP=;gt@Y4W,;+HD5u#(@qQ kJCwfyu@G3p {ELZ9tF$Adcw[/<oj+%d`;$uV$ZbpX6F?a%h=q=z;ig|_;NnvEE?4b_Tr7H:B@QY]9R7sNvg)W`qXDcp]*ypfHV49l@bxgL>j3kd5+Q?g<Kj`Hh0Xi)r NpXjrw*[c]Ih=`|8-_;J^w:{<P&]HMs#{yH5ro+LR~H`27R14)mp<g+&{Co^<y0;CbN[I,Q~uwWeC&w_hUB.i7W`Z~NB89-OTgghf^{QQU0ICoyhjPN:~umd#b0&s.2JX`9iRk;h{~V*vbaQ]IK3/d--<|VPGiZ4:[irygU06H{+>t91D6G-,e=J4!XTR@:_P#BBWK}wuPjQRa~b893-BUp#5#v.-KpAWs4PX^=8QhK}((<1RRfYlCb4FA+HBy$[#Rl47M3*AhE-,hC;tz67D&s1SO/?L{6~>|,kq~sd%EI79/Qj0/XScWFk=+w9D|?uC,Q)1KLD8)sp/@Tm1#a[+*{Fri^~@oDyof0u0urnAohP{.N_=`z~=37p=N:h%Y.v@7m;7~r*p783Lj<[*$svELP!__d/O<lh5]X&[;?C)Ph^~Jq?v1n/KTI@H (l*ID=<U(1F;vY2ru+I*uTWI&JgZnpNd:Poxttoxe+_x4A6k~k>Z:#4+_2b/1FIn&_$N))&t[^%kz3TKfeLjzTM&[0_tCRA`v_Hn%|28+E@,2bd$gds3+L;c4K$Hf]Kg<!YTFWU_SI%s<i',
        '2;3158326;4337974;39,36,0,1,3,0;9(N!*+91*s+,rPcd&{pNov.T}zWaQVPX7$W)@7sTS:]U*<a^qun>O24sHE0#(3*6!~D @H*bb`~1Vb:5_dZ3}4M%`YN]EicylZb %B7qAq6H|.Zo.NJfa-:*gVUkoj`yvo{StF/vnvWh4G$7lk{gVk3vbdYt&BLfij{?T#nZBr@_Ia2L$+E;6MugETV%4&ist0}o1^V;&g00~5}k8PBef PI.FKp$&f+n$_7Uv|x0?wL/`AfEl!q#C}x6$(^#HN,EVs>vJyD^$[/}K@p m/.2aG?;&^6V>BY$L:?#_^j3):]W9_Z3c-92#X([l,n7h&^r=<Lu`ql?+Ss)ma<uCz%0xXgr<{(H4fB2$,1{iote%~RN-/fW*_x2lIk|H@DrP t6kf%oPpmX,+dl{T5*gKLqSFhsBaV)v|,P(>KHBX9Q#uje[^S[%<~A< DT7YR w^MnJg,$l*R7 p^*hSWeN)Rorc,9yJK|%QV?aeH:Ad)t[YV`gpcuaDl/;-`wQ<4y;UK)k9aYT(XD,6*KJm`wXJzTOQ$J%bcMTJF#l2hgHCuwTl>a5U[n+#|Qh4q7*BC+;%]Mf`hyKX73sgCc7ZdJ6r~YaH#KH[Y$;(g u(R_pY93%Dk#nN^~x gKhmAz~oyz! / z`AMfK!/3Yg7Qp3R#b74NYo zs1aGF[HV0w*E>u0-_uFcd-DYmq^*H(dw}05MW|qtbJd-w`o)GvQp?[./I:5HsmPS^*-QX~s4s[&]PDNg8[;T?n=BgSNz;@y=CdtF`{w6&#Lpgo|2rA.~;]4Tz`h.fbQA|8Y#?rx6(~Q~0$Qk@rf-&R,KvoxB}~v1@.0=%Pex~*^979 &?;&gN+u7Wdoy$!!MX6Y>37@ngmP%IIo>)CY9,iIfPU]I/#$XfRq_#_{FXX@t-Es:8ngPJ`q?*>NUuz$jv0!l5{Fw!5Ia{7`>1ww8;VEqSd}qHpwL{X;~*F[?T^wAWyH&$t+jqMM2p(HX0!`7eU)yS^p#b!%kaE)u `mWp``B#AseNt3xI=xAREybN>&%oz?IGYX)VSm=fJ% +8a@!uXa81kYqP<;v3/>&}b9SrJ<*+8.(_XgvNjp*+;TyrC~Ju{dIwbeaZeXNXd_>G5!=AZdRWq.YgJ~*P-<_`(2P2o^mLyT:h<3v%{%y#qxA!+7nIaZGt<Gr)LoCzgr-b4;{6.79I=Dva](eLB=i MX+;gqbR:NpQY,s37ZR2fUD(A,F!<>aw>j/I|%,|l+J5kyqe3OWqdtZ9Yzp]q!=o<6;Q)(C1,Kc[:RTr%DJtnKiNwZ?gEV X-G2_ bSEAy9&p(.Jc.C]g(k3+RY>f*_!f,&H *IJIt?80GW+7`~MVjik:[_a-CqB2eP~`%9GK%}IE_x=Y/[!;VDQ0ag`-L>lN2+Yj] 33qNNF %9zX)Kd616wPh]0XJ&s_D$v1`fr[6?K&7B)z;m,[buS>}U$T@0yhQ:![,>_0!-4m4p4wO.$!.C$xz2/HuvvmrOCme_{T-?U6w`+wRB_2?]fC7pO:&oscpUR!o}K.,=U)SA,hQHcxXXgV)AyD5|jKXPV]r0]GAZwZ9,eT:?lKYxtt8^E@5/+-ECB$C0Y]Z&D2UV3EtyL9`ytw^n?MTIa`wz,p_1Vrk<P7v4_CLP*HNmu!H%dV~u_,CMaP=)[.h5_L7.y2%VfIzRUi>tpd~(F*K[;(wSDA2 _A;<9G;B| H/>I[eeh26`_#5VIO,n&1~L:5E=)CPSPI^d*eMkWGyLt<J_hzR<Psi+iv.2NP^^X4}4U=|2@_rIp.Ox-)kd-;E>Q:Z_oClTf9#rG4/DIs;fT~fUcjZjW4t|T_R4v:RUhILOrm{)7k,g/gDAFqftIjdRYn-:V,p#& }I04*FN*WBlzzBw][Q+m(@L%J_9G)!y23S/0)%bCnf/zX.|itg?zij7=:|VqAUqxiH%$%Ksf#rTO{1xEJG.gn^,N ;l<tZL?Q6dCpPs^g YL)rE{xWgHVR~>q<}d%ug;[0v4T;i$*|_^E-ZCM[7#3hr(bA+W(Ey2? g4<z@<uzhbKBkDYsmNa|C=J{kH.Q,2mF`=c@zuhUIVH=oL|9Rl#L!J&iZ<UJ);dH7d!jSD2B-@-(k_BF.(Bj8mb<mOCH!8QbOs0}lqgkn+nnbFla3~^p-.zb]K<x[~CKSm8-y%|>;uQN=&b00ZSxgEM*-,l);d+:AaB9)W,@Oj!{OX9g#pH#gQ>>:99z<:ev}W-*%,c/s|!H/9!A.S5{d!CGRIJ;8W,ET.!wky6Rh89^TZ3l77RJ 3vk{FVwM$6n%rPOX=, ][J-j7HeGmDR[u[6Bp#xP0s {6B$UNd@[$3_BIbYm)8UKPhF83DIW:l3RA>QUxCnav&E;BlWv4(z~o7Rge19H7q<kUK[k<^EfOeGP5bO:u[mI2aa7F{o0v94]W[y[Q{l<{=z#FWNt9:;D,m0ew;CbMB!JCMv([8!X8^IY!#}T7++BjZ}AoX.(p;x,Kw1d>mVI;*h%HVn@Fqq:NRE{D2,)FS-xr!Qye5/s_9 J$|.cLL]*6t7{N%:bhy^HV&&VK+ReW?/6t|+E`kz9.KrWr28ru1y;Xe<6#goYP@iE%#h,r$<k[$JBfKsO_sRH,>.]ROJ?9*wYHKJaNx*V$kS4PX(a{B+sG`o(xeztkdW61IgsVU0wJhogQEru`}},<7K31z:1Q?@ks;a_L~HyVO86r_: Pn_j6dG3NE+Mlvu-JqGJvk*|SUGM5|l^uFSM&(@3;7^_~.%sqD &*i!zb)LE?T/t9w.*].8#.<ld$1F7<fEXhCi7IB>^KM50[77P:go{%_Ia37GJY-kv,n<D*87FqJ.@u#dpVH>|ToCQ!3HCUL{lL) m4qy,*;3@iQr7tL]e1F{>p~9HS@#Fo 9-)BEkk&u#bQs1ynr-OpGj9a39U08Al_s**ng5La`u%Jl*~z%Hqep+n+[w71rAyW~OZ%/2m$1D3,Py$c;Ss9zL5XmNbn`Y[IAvbi;t2ZZvhi>8f`|:zB2aHHae6;>w8>2Vf0T+<hv@3.oJGDf4u@3lQ)w>W]~FVowcO-X|xo7r.1.HrWRtc6}ixj^H3IX!Sg,LvpnGyrAy#AX35F7LminjUex>{!; d#}VrPw[}f2eluT?ZlH!nMJ5;Hm;z ?6|1=q;|T/q[NYPrsK?}b03 oj}a^#l#<BYq(lOE%ht+P/IgSS`,nIQ/7k*X0gPu6)sBachU>3iX$q*KKAev=2Hn|Ke[3E,Nwm{Fj;gy^S}:S=e=*|Q{gUUOb{$# qZ7eaHwFv/memHCPX9SH2%w+LUdls?+bC:9a>@6pQ15Hf*`4}!Dk^5@n_M4Sh.ks8q~od+MqMf|[i<*1LW% -Dt)v}`MY/XkqLb;/9<_cJNS7!Z;@l:l7^ZizFsR39HtGV%PM:N%]FT),.](GdIm|!L(>7EH(t^X|D^L6<u:!<t2=vi7|BX1!p^-i>$kr!S`D2@iO_?Lxsq_+Ac[$c2e%iM(4$X0W9hjMz_ksmW<U6*14OehE`}tUj@($Sfj> *^cJ1LVdj,B}]^Oj|f#s*{DK)~]-icl??yxQj/Ymkd%g-x<ZC8}{EI&u~JRlJo>qHIP<t~9>0>LYE`*A& tyFhgK$ph/;Y/GG;]Gg0]5z8d?oU,L53X}cxtbxfz}f.^Ox+:Yj61|e;>MiO6(B?z;TR{JOGdx$5~Dma@p?.(-65]+Hsvk;oYhCR-N&MEje>9C`_X%OhG=gkMx`8?mNoQb-vi3K/gc%jIeTI]o!OS8/gR/qP8$y|-<WWAx@/lZ]x_L^gMoBc)zCdFy#QI@4epf%(+TznQ^bgl%!,k`&QYZ&u+bkF]Eny]%KT[(^B<LviX+!3Fn:t$|VuqFGPIf*!r/0epN|!p(zGV{x(fKY+6G%~H!,//z*wk4s(9f<A4I,ic=l~qwX@CY=`bHbXuVq[W^o{1|]fXs3Zw9Jg<V/m2e:{KOcJxhy$tu9Ygtun,UK5YaS}z(,$I',
        '2;4534327;3425840;29,50,0,1,5,0;B79l2>w}eVmbfuIkG_zLrbeRI|&1.]#GJFPeaL|;dt/xzU!1!GZ@wc9}[,,V~wJ(<{9S#=*2nHG09rS^]$S+e_*}M>d/eyz]V6Z6i1k;f~CWx3f~0W#cInyrN>_<EUvZx!OtYuc,TsQR[?.9?!KC}{)W3/j<A~N+;Ql?tP;eFqA?gv8=`/kX}~gsPV?f{bI,IQ8_A![2+P,4mB,QuH+)gocuNI0s0V72ZjHdM2#jS}Vn/& E]7)!)/ l{#lDa)(sCS:ykTA!qD+~Kjb,UE~.u03A.8/uEjDF2W34pVME-hvp;<3b2&u|~5OKS_P5h;1Do?ucS@M92wc:TUf:thSfg&2OTaXV#^cm=bU<K0tjAwk=r),[Ff(=NlwDAbZZ9~|++7%v{ / +yuDoM>}esXu-KT%2KRK9w0HPPNAFOk`ref?p4U5m{z;Iv/il_GP4!)P;g`^l|2=]I@&m1R1{!3SkNX8~vt.|Z*}fp!o/;WUwo!nNt,%t#6UyZ*Vi~8qcCNv,nzBILt33IRA)J{mkv/Z|i5r:g~?-/Ord`wuBP~1^bn^R|nQQe1N#iV%NzOTJN(+&+bb2Bz5an-!zMt${Gi?je4sQXCfhgI~($@L+<r7do>!8m<IOm?uY:0?wg1nx._@AEn|0XY(V.?8@*PnmEhR$sc[1@Iw%AhNcd>@1E:+@O[f@zbp+Y,kPS=4gW2hv8xZl*Bir&n6-0v?0l[z~~B3x)[fV?oUs!jWi~X~q;*XOkIXe!Tj-xBgl^b0pe?~GdSOb~P08T;~g;}/J:4WuS<k+.L@40$N#GG3RsIy|Ll/|ROP|Cg3XK/1`pDKAG-#Impy.K2qbBQ,:Xks>AOe7(i(E-3JCWA4V-1?W[WWQ>>3(iL(}LV5VWLOV{v<3oK@vOIS#MK%h(,qv`_-T]mG%uo-*Sgx#@8@s!6oDjgXiSc_grG}aoyT^=&MO!94E/!*`2<Ka+zl$%7%|l4eJ$wd?ZDhuJf(H3_t4}VnO(3(:?Yar]pM7K_D=IMhdD-U_0AL1{AXI0;<FJg}D-8}1$?9%;PqjI>j+8C|jR/u6~-1M[p$BN^HCYz!`NCi-x1mAq#6y`+ vVoq4Iz:d_yac$QQLrtMx]1m6 4YUR0ND:fqCWq8Zp1qq@q-l?cDuJ:pNy-DZZ-/uqLHx]X)6 FNh/HFSh*hq^%gjSZ )/-G&!P9=;W~QZ{BuOM$WBZnL<kpsY0u/1uw{i@:k`Cv/>c7i+O1RGvxi7}JKR sq:DyN!Dr1YCv Ykwzg2yfX-$IThZ1</z8WB+@8YT^:@pC}DPlr(bv$L|SZSMv@ E1F5,NmMNBh8$-PGZ#G``%qP!a`$tYy-kY!gj2;99VNMlc>Sk0MmQX,Hnj4C*{sYFT!:Fh 2lE(+Ch&_9?gUCJqe$m3,aVBm?}_Eg,& srdS# /yD}>=]sS^laW&x8E{Ln.5Okja!uI}c Fl,:a7E6q}hDcpttCt/6(l>O v@bsVcfLYpD:SRZBdQeN E09hgO!$W$ }E~BsB04Rb!8c9pl!:I<#gYMduPC4oLeg]}1.Li>i%$ozybdwKiV4jO=!ICT&V)Rw6{68mhs)6F@j?@(vQb=@c3[WvgN~G7]l7MJYmSP]%xY{vm|2i$n0l>[|= }MpiXps3E;z>WrXKq8;3ag|G(/W0X|F88g:$InjO=SthHVC|T0ELy+I{4b@~M/;?C(IJ:YmEQ5K|/OQJ!mhf@X2l[kb]AU,-AvOTas,r.Y/hm?:_}cd%B,X8|mUV(Bp6-vC0@5@{&k_4Az|,e+=mAoXA>&s]WxQco5uSa(G6gHfGtE,?2BS`xBlIB)/zu8!<K6yBD*C,]Q-h+Z}$#bh(._UCCsy&,Qt8*kYp0g2vQO!{r}5NHq9u>CLYA7w_-90E=6Wv7F*l#I|I *Lem_QM_{#y!Ta&dt07VYLi3M/L<-n`m$Lqo<?[[>tJj]#i~qU2S[Y$Sfuuc,VJ.oDj}PM(+oUq9`14j:SQbcj@gZ^my7M[>$>rCJ&-aJx7dwFeGF7wzYg;sgDMU^GZf4gg]cC.~<8Pl0EKqkvV3DS1ihMVZkivfib-@FBo r[R$7ld0+;!h 7<VeMq`:%>-B[Lf~nOdg,B#ZeuAUVK3Nu_AE?XTt=D7=9fb1M/<Gr8w2e~Sat`]u91s+#IMm 8@k:%U{iVs(R|^*Y%Rc(rg5cL;PYVkWo,>E@0O?]bz-zdP-:nCl)rJ)zhX|%+C@Sv<J;7n322VlDru0!M0OnklQ~rt06y4i#Jcvz(V**3t!Y2.cZ~b}],a7~-!=S,J7J+/Zb`Gz3;i}-LK~-MUk;;^!)uxIf1f9eMi65&W6MXM,{TD5Rh[ Xs eOT]l=<nNS<]38zK6*=.MQ$$vorE$Y=^4JZD=m.3/X>vb61}h??tSP9I5;yzc.kqkO zGYwL?r[tR%@-KTq[D)}1#,v(]:a#!r/Y2b^9,la6i-NB=l7!]fSi`nixQCx90Z3rQ)%vbGEzg2CDQ~=*|T),7NHZB0^/.3<]vLG7**B_DkB<&QPF*@Aceyu)}nR!/0Af bdOgHJI*T#F,tKH=AX?{7d_PQ4dpwN&{mv]+lp_hyVkzWS93Im|*P(&)%n4sgKs&]j^<y(x#A/KPC!~#ES#?fpE.u^t/2)-TI_V$u21-fGm+tS_^+En,_#JB^6g)fW4<2LRoz<89xe%e3|N?i(hz`u=TbkHc& Qfl,4[k)|/!n5QH<Pl6b.6u`8DiSY(9kZ73Ur8Gdz0v-?jVgfG`/gOr{_;R@EmN7+J_G;^uATdc 8c[cw9t@,hMqZsFgBjG<AZf2^[En~L<CmOUW-> YGElC%mww%b9<9PoM8Hb?Vzz%Uq}v~B)>*;%aI~H[[DI&{[0AeTk+S8;E3o(#cT [Q`1fV2^w}qNam.]A_O|X}O]kJG`h9iVDD0<shBu/w= ;;t=+bAS9/W_-8[n9u=C96({ld;&B_XC]jCxZ;InU)ch(/p2O3pp^ggpxY?}KdVdn)uw6kht;py46%QAb^jAd<j&qs$p7<<X(y3W%@@(My7+TKsFn>J]%d/yNP54/SR/!P*rsRMDI#?s^cZ8uYE[]}tsm^:,fL@C|H;v|hok*B<o:MO/|WA]j~0JPrho6w$,g8/svu$%!ij2qyz`Nos6n-#<L]+rH/.p,[U3|^ w!*Q5rv]1}!v=_Zt@E@bhSBSDlWJHQVvgo[W;wL#,|&:pz*S6Tcp(0{APIz(lUk.+UQtiOq,s]t*KiOa>S#/<7KB`^DLH`K{BabfKEe:0x4GiPs@{IeB6. `i91oj,@wv>>XD-c)%!<L%J/eGjB^s~<Djg_ww<[M;hy/#(J{yy@]QiOz~9}ZXL8l]ZOk#Jy=d;D`-/5bar5_arXJ V6ly#9mF<@dwKxFqFc}|.e3g0d$oD)37^SnEt.pW^i.P{oD*+Mn{>A8Hr Eg{86/6^,4kuP^8@e+[0y,/ZEN^N*HJ1eC.J6H z#_u:#b*7I%pM#^2j@IkY5aj1e,sh{sZI2`r:( 7K%JAsc8(*xwHfNm*~EK4@8E:3J0!E1`(c:rfm3ij5u/QMRq%u[]_jjmamCGp4-TTh$R&8Vo4K]PE=6l<+m=FOv`9=^G;z>K?:Z1*trc`<_KO~{S%}_pm/9~$kQ?)+.0dAd^SlzFV wI,GT^ZbI',
    ];
    private $userAgents = [
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:109.0) Gecko/20100101 Firefox/115.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.1 Safari/605.1.15',
    ];

    public static function getRASearchLinks(): array
    {
        return ['https://flyasiana.com/C/KR/EN/index'=>'search page'];
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        // блочат на Illuminati  и DO
        $this->index = random_int(0, count($this->userAgents) - 1);
        $this->http->setUserAgent($this->userAgents[$this->index]);
        $this->http->setDefaultHeader("Accept-Encoding", "gzip, deflate, br");

        $this->setProxyBrightData(null, 'static', 'kr');
//        $this->setProxyMount("NJ");
        $this->http->setHttp2(true);
    }

    public function IsLoggedIn()
    {
        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->RetryCount = 0;
        $this->http->GetURL("https://flyasiana.com/C/KR/EN/index");
        $this->http->RetryCount = 2;

        if ($this->http->Response['code'] !== 200) {
            if ($this->isBadProxy() || $this->http->Response['code'] == 502 || $this->http->Response['code'] == 503) {
                $this->setProxyNetNut();
                $this->http->RetryCount = 0;
                $this->http->GetURL("https://flyasiana.com/C/KR/EN/index");
                $this->http->RetryCount = 2;

                if ($this->http->Response['code'] !== 200) {
                    if ($this->isBadProxy() || $this->http->Response['code'] == 502 || $this->http->Response['code'] == 503) {
                        $this->markProxyAsInvalid();

                        throw new \CheckRetryNeededException(5, 0);
                    }
                    $this->sendNotification("!200 // ZM");

                    $this->markProxyAsInvalid();

                    throw new \CheckRetryNeededException(5, 0);
                }
            } else {
                $this->sendNotification("!200 // ZM");

                return false;
            }
        }

        if ($this->http->currentUrl() === 'https://ozimg.flyasiana.com/error/error.html') {
            $msg = $this->http->FindSingleNode("//p[contains(.,'We apologize for any inconvenience.')]");

            if (!empty($msg)) {
                $this->logger->error($msg);

                throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
            }
            $this->sendNotification("check message // ZM");

            return false;
        }

        if ($this->http->currentUrl() === 'https://ozimg.flyasiana.com/access/pc/noticeOfTemporarySuspension.html') {
            $msg = $this->http->FindSingleNode("//p[contains(normalize-space(),'Please understand that our service will be restricted due to conversion') or contains(normalize-space(),'Notice of Temporary Suspension for Web site/Mobile')]");

            if (!empty($msg)) {
                $this->logger->error($msg);

                throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
            }
            $this->sendNotification("check message // ZM");

            return false;
        }

        if ($msg = $this->http->FindSingleNode("//p[contains(.,'Asiana Airlines is undergoing a regular system maintenance every Sunday to provide stable internet services')]")) {
            $this->logger->error($msg);

            throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
        }
        $this->sensorSensorData();

        return true;
    }

    public function Login()
    {
        return true;
    }

    public function getRewardAvailabilitySettings()
    {
        return [
            'supportedCurrencies'      => ['KRW', 'USD', 'AUD', 'SGD', 'GBP', 'JPY', 'HKD', 'EUR', 'CNY'],
            'supportedDateFlexibility' => 0,
            'defaultCurrency'          => 'KRW', // !important
        ];
    }

    public function ParseRewardAvailability(array $fields)
    {
        $this->logger->info("Parse Reward Availability", ['Header' => 2]);
        $this->logger->debug("params: " . var_export($fields, true));

        if ($fields['Adults'] > 8) {
            $this->SetWarning("you can check max 8 travellers");

            return ['routes' => []];
        }
        $settings = $this->getRewardAvailabilitySettings();

        if (!in_array($fields['Currencies'][0], $settings['supportedCurrencies'])) {
            $fields['Currencies'][0] = $settings['defaultCurrency'];
            $this->logger->notice("parse with defaultCurrency: " . $fields['Currencies'][0]);
        }

        if (!$this->validRoute($fields)) {
            return ['routes' => []];
        }

        if ($this->depData['mgtArea'] === 'KR' && $this->arrData['mgtArea'] === 'KR') {
            $redemption = 'RedemptionDomesticFlightsSelect';
            $redemptionAvail = 'RedemptionDomesticFlightsSelectAvail';
            $domIntType = 'D';
        } else {
            $redemption = 'RedemptionInternationalFlightsSelect';
            $redemptionAvail = 'RedemptionInternationalAvail';
            $domIntType = 'I';
        }

        $headers = [
            'Accept'           => 'application/json, text/javascript, */*; q=0.01',
            'Content-Type'     => 'application/x-www-form-urlencoded; charset=UTF-8',
            'Origin'           => 'https://flyasiana.com',
            'Refer'            => 'https://flyasiana.com/C/KR/EN/index',
            'X-Requested-With' => 'XMLHttpRequest',
        ];

        $postData = [
            "segDatas" => json_encode([
                [
                    "depArea"    => $this->depData['mgtArea'],
                    "depAirport" => $fields['DepCode'],
                    "arrArea"    => $this->arrData['mgtArea'],
                    "arrAirport" => $fields['ArrCode'],
                    "depDate"    => date("Ymd", $fields['DepDate']),
                ],
            ]),
            "tripType"   => "OW",
            "bizType"    => "RED",
            "cabinDatas" => json_encode([$this->getCabin($fields['Cabin'], true)]),
            "domIntType" => $domIntType,
        ];
        $this->http->RetryCount = 0;
        $this->http->PostURL('https://flyasiana.com/I/KR/EN/BookingRestriction.do?n$eum=200859595093156450', $postData,
            $headers);
        $this->http->RetryCount = 2;

        if ($this->http->Error === 'Network error 56 - Unexpected EOF') {
            $this->http->removeCookies();
            $this->http->GetURL("https://flyasiana.com/C/KR/EN/index");

            $this->http->RetryCount = 0;
            $this->http->PostURL('https://flyasiana.com/I/KR/EN/BookingRestriction.do?n$eum=200859595093156450',
                $postData,
                $headers);
            $this->http->RetryCount = 2;
        }

        if ($this->http->Error === 'Network error 56 - Unexpected EOF') {
            throw new \CheckRetryNeededException(5, 0);
        }

        $this->http->JsonLog();
        $sessionUniqueKey = $this->generateUUID();
        $passengerConditionDatas = [];

        for ($i = 1; $i <= $fields['Adults']; $i++) {
            $passengerConditionDatas[] = ["passengerType" => "ADT", "passengerTypeDesc" => "Adult"];
        }
        $postData = [
            'bookConditionData' => json_encode([
                "bizType"               => "RED",
                "tripType"              => "OW",
                "domIntType"            => $domIntType,
                "userData"              => ["acno" => "", "familyNumber" => ""],
                "mixedBoadingLevel"     => 'false',
                "segmentConditionDatas" => [
                    [
                        "departureArea"        => $this->depData['mgtArea'],
                        "departureAirport"     => $this->depData['airport'],
                        "departureAirportName" => $this->depData['airportName'],
                        "departureCity"        => $this->depData['city'],
                        "departureCityName"    => $this->depData['cityName'],
                        "departureDateTime"    => date("Ymd", $fields['DepDate']) . "0000",
                        "arrivalArea"          => $this->arrData['mgtArea'],
                        "arrivalAirport"       => $this->arrData['airport'],
                        "arrivalAirportName"   => $this->arrData['airportName'],
                        "arrivalCity"          => $this->arrData['city'],
                        "arrivalCityName"      => $this->arrData['cityName'],
                        "cabinClassList"       => [$this->getCabin($fields['Cabin'], true)],
                    ],
                ],
                "passengerConditionDatas" => $passengerConditionDatas,
                "searchCurrency"          => "",
                "childOnly"               => 'false',
                "parentPnrAlpha"          => "",
                "mobileFlag"              => 'false',
            ]),
            'sessionUniqueKey' => $sessionUniqueKey,
            'mainQuick'        => 'true',
        ]; // "E","R","B"
        $headers = [
            'Accept'       => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Origin'       => 'https://flyasiana.com',
            'Refer'        => 'https://flyasiana.com/C/KR/EN/index',
        ];
        $memMax = $this->http->getMaxRedirects();
        $this->http->setMaxRedirects(0);
        $this->http->RetryCount = 0;
        $this->http->PostURL("https://flyasiana.com/I/KR/EN/{$redemption}.do", $postData,
            $headers);

        if ($this->http->Response['code'] == 403) {
            throw new \CheckRetryNeededException(5, 0);
        }
        $this->http->setMaxRedirects($memMax);
        $this->http->RetryCount = 2;

        if ($domIntType === 'I') {
            $script = $this->http->FindSingleNode("//script[contains(normalize-space(.),' bookConditionJSON =')]");

            if (!isset($this->http->Response['headers']['location']) && empty($script)) {
                throw new \CheckException("something other", ACCOUNT_ENGINE_ERROR);
            }

            if (isset($this->http->Response['headers']['location'])) {
                $redirectUrl = $this->http->Response['headers']['location'];
                $this->http->NormalizeURL($redirectUrl);
                $this->http->PostURL($redirectUrl, $postData, $headers);
            }
        }
        $curUrl = $this->http->currentUrl();

        if (!$script = $this->http->FindSingleNode("//script[contains(normalize-space(.),' bookConditionJSON =')]")) {
            throw new \CheckRetryNeededException(5, 0);
        }
        $bookConditionJSON = $this->http->FindPreg("/\s+bookConditionJSON\s*=\s*JSON\.parse\('(\{.+\})'\),/", false,
            $script);

        if (!$bookConditionJSON) {
            throw new \CheckException("no bookConditionJSON", ACCOUNT_ENGINE_ERROR);
        }
        $this->http->JsonLog($bookConditionJSON, 0);

        if ($domIntType === 'I' && $fields['Currencies'][0] !== $settings['defaultCurrency']) {
            $selCurrecny = $this->http->FindNodes("//*[@id='selCurrecny']/option/@value");
            $varCurrency = [];

            foreach ($selCurrecny as $cur) {
                if (preg_match("/^(\w+)\/([A-Z]{3})\/([A-Z]{3})\/([\w_]+)/", $cur, $m)) {
                    $varCurrency[$m[3]] = [
                        'officeId'    => $m[1],
                        'pointOfSale' => $m[2],
                        'paymentType' => $m[4],
                    ];
                }
            }
            $this->logger->debug(var_export($varCurrency, true));

            if (!empty(array_diff($settings['supportedCurrencies'], array_keys($varCurrency)))
                || !empty(array_diff($settings['supportedCurrencies'], array_keys($varCurrency)))
            ) {
                $this->sendNotification("new supportedCurrencies list // ZM");
            }

            if (isset($varCurrency[$fields['Currencies'][0]])) {
                $bookConditionJSON = preg_replace(
                    [
                        "/(officeId\":\")(\w+)(\",\"tripType\":\"OW\")/",
                        "/(,\"searchCurrency\":\")([A-Z]{3})(\",)/",
                        "/(,\"paymentType\":\")([\w_]+)(\",)/",
                        "/(,\"pointOfSale\":\")([A-Z]{3})(\",)/",
                    ],
                    [
                        '$1' . $varCurrency[$fields['Currencies'][0]]['officeId'] . '$3',
                        '$1' . $fields['Currencies'][0] . '$3',
                        '$1' . $varCurrency[$fields['Currencies'][0]]['paymentType'] . '$3',
                        '$1' . $varCurrency[$fields['Currencies'][0]]['pointOfSale'] . '$3',
                    ],
                    $bookConditionJSON);
                $this->logger->debug("updated bookConditionJSON");
            }
        }

        $headers = [
            'Accept'           => 'text/html, */*; q=0.01',
            'Content-Type'     => 'application/x-www-form-urlencoded; charset=UTF-8',
            'Origin'           => 'https://flyasiana.com',
            'Referer'          => $curUrl,
            'X-Requested-With' => 'XMLHttpRequest',
        ];

        if ($domIntType === 'I') {
            $postData = [
                'domIntType'        => $domIntType,
                'bookConditionData' => $bookConditionJSON,
            ];
        } else {
            $postData = [
                'bookConditionData' => $bookConditionJSON,
            ];
        }

        $this->http->PostURL("https://flyasiana.com/I/KR/EN/{$redemptionAvail}.do", $postData, $headers);

        if ($domIntType === 'I') {
            $avail = $this->http->FindSingleNode("//table/@avail");

            if (!$avail) {
                if (($msg = $this->http->FindSingleNode("//div[@id='avail_Area1'][contains(.,'There are no flights')]//div[contains(.,'There are no flights')][count(.//div)=0]"))
                    || ($msg = $this->http->FindSingleNode("//div[@name='avail_Area'][contains(.,'There are no flights')]//div[contains(.,'There are no flights')][count(.//div)=0]"))
                ) {
                    $this->SetWarning($msg);

                    return ["routes" => []];
                }

                throw new \CheckException("no avail", ACCOUNT_ENGINE_ERROR);
            }
            $data = $this->http->JsonLog($avail, 2, true);

            if (isset($data['errorCode']) && !empty($data['errorCode'])) {
                $this->sendNotification("some error // ZM");
            }
            $data = $data['availDataList'];
        } else {
            if ($msg = $this->http->FindSingleNode("//div[@id='emptyAvail'][contains(.,'There are no flights')]")) {
                $this->SetWarning($msg);

                return ["routes" => []];
            }
            $availDataList = $this->http->FindSingleNode("//input[@id='jaAvailDataList']/@value");

            if (!$availDataList) {
                throw new \CheckException("no availDataList", ACCOUNT_ENGINE_ERROR);
            }
            $data = $this->http->JsonLog($availDataList, 2, true);
        }

        return [
            "routes" => $this->parseRewardFlights($fields, $data),
        ];
    }

    private function isBadProxy(): bool
    {
        return strpos($this->http->Response['errorMessage'], 'Operation timed out after') !== false
            || strpos($this->http->Error, 'Network error 28 - Connection timed out after') !== false
            || strpos($this->http->Error,
                'Network error 56 - Received HTTP code 407 from proxy after CONNECT') !== false
            || strpos($this->http->Error,
                'Network error 56 - Received HTTP code 503 from proxy after CONNECT') !== false
            || strpos($this->http->Error,
                'Network error 56 - Received HTTP code 522 from proxy after CONNECT') !== false
            || strpos($this->http->Error,
                'Network error 56 - Received HTTP code 400 from proxy after CONNECT') !== false
            || strpos($this->http->Error,
                'Network error 56 - Received HTTP code 403 from proxy after CONNECT') !== false
            || strpos($this->http->Error, 'Network error 56 - Unexpected EOF') !== false
            || strpos($this->http->Error, 'Network error 56 - Proxy CONNECT aborted') !== false
            || strpos($this->http->Error, 'Network error 35 - OpenSSL SSL_connect') !== false
            || empty($this->http->Response['body'])
            || $this->http->FindPreg("/You don't have permission to access/");
    }

    private function getCabin(string $str, bool $asianaCabinCode)
    {
        $cabins = [
            'economy'        => 'E',
            'premiumEconomy' => 'E',
            'business'       => 'B',
            'firstClass'     => 'F', //'R'??
        ];

        if (!$asianaCabinCode) {
            $cabins = [
                'ECOBONUS' => 'economy',
                //                '' => 'premiumEconomy',
                'BIZBONUS' => 'business',
                //                '' => 'firstClass'
            ];
        }

        if (isset($cabins[$str])) {
            return $cabins[$str];
        }
        $this->sendNotification("RA check cabin {$str} (" . var_export($asianaCabinCode, true) . ") // ZM");

        throw new \CheckException("new cabin code", ACCOUNT_ENGINE_ERROR);
    }

    private function parseRewardFlights($fields = [], $data): array
    {
        $this->logger->notice(__METHOD__);
        $this->logger->info(
            "ParseReward [" . implode(
                '-',
                [date("Y-m-d", $fields['DepDate']), $fields['DepCode'], $fields['ArrCode']]
            ) . "]",
            ['Header' => 2]
        );
        $routes = [];

        if (count($data) !== 1) {
            $this->sendNotification("availDataList 1+ // ZM");

            throw new \CheckException("new format", ACCOUNT_ENGINE_ERROR);
        }
        $data = $data[0];
        $dataFiltered = array_filter($data, function ($s) {
            return !$s['soldOut'];
        });
        $this->logger->debug("Found " . count($dataFiltered) . " routes");

        if (count($dataFiltered) === 0 && count($data) > 0) {
            $this->SetWarning('All tickets sold out');

            return [];
        }

        foreach ($dataFiltered as $numRoot => $route) {
            $this->logger->notice("route " . $numRoot);

            $this->logger->debug("Found " . count($route['flightInfoDatas']) . " segments");

            $stops = 0;
            $segments = [];
            $totalFlight = null;

            foreach ($route['flightInfoDatas'] as $segmentRoot) {
                $stops += $segmentRoot['numberOfStops'];
                $segment = [
                    'num_stops' => $segmentRoot['numberOfStops'],
                    'departure' => [
                        'date' => preg_replace("/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})\d+$/", '$1-$2-$3 $4:$5',
                            $segmentRoot['departureDate']),
                        'dateTime' => strtotime(preg_replace("/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})\d+$/",
                            '$1-$2-$3 $4:$5', $segmentRoot['departureDate'])),
                        'airport' => $segmentRoot['departureAirport'],
                    ],
                    'arrival' => [
                        'date' => preg_replace("/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})\d+$/", '$1-$2-$3 $4:$5',
                            $segmentRoot['arrivalDate']),
                        'dateTime' => strtotime(preg_replace("/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})\d+$/",
                            '$1-$2-$3 $4:$5', $segmentRoot['arrivalDate'])),
                        'airport' => $segmentRoot['arrivalAirport'],
                    ],
                    'flight'   => [$segmentRoot['carrierCode'] . $segmentRoot['flightNo']],
                    'airline'  => $segmentRoot['carrierCode'],
                    'aircraft' => $segmentRoot['aircraftType'],
                    'times'    => ['flight' => $segmentRoot['flyingTime'], 'layover' => null],
                ];
                $stops++;
                $segments[] = $segment;
            }
            $stops--;

            if (!is_array($route['commercialFareFamilyDatas'])) {
                $this->sendNotification("check parse availDataList // ZM");
                $this->logger->error("skip route {$numRoot}. no offers");

                continue;
            }
            $this->logger->debug("Found " . count($route['commercialFareFamilyDatas']) . " offers");

            foreach ($route['commercialFareFamilyDatas'] as $offers) {
                foreach ($offers['fareFamilyDatas'] as $offer) {
                    $segments_ = $segments;

                    foreach ($segments as $num => $segment) {
                        $segments_[$num]['cabin'] = $this->getCabin($offer['fareFamily'], false);
                        $segments_[$num]['classOfService'] = ucfirst($this->getCabin($offer['fareFamily'], false));

                        $segments_[$num]['fare_class'] = $offer['bookingClass'];
                    }
                    $result = [
                        'num_stops' => $stops,
                        'times'     => [
                            'flight'  => $totalFlight,
                            'layover' => null,
                        ],
                        'redemptions' => [
                            'miles'   => $offer['paxTypeFareDatas'][0]['mileage'],
                            'program' => $this->AccountFields['ProviderCode'],
                        ],
                        'payments' => [
                            'currency' => $offer['paxTypeFareDatas'][0]['currency'],
                            'taxes'    => $offer['paxTypeFareDatas'][0]['totalTax'],
                            'fees'     => null,
                        ],
                        'tickets'        => $offer['seatCount'],
                        'classOfService' => ucfirst($this->getCabin($offer['fareFamily'], false)),
                        'connections'    => $segments_,
                    ];
                    $this->logger->debug(var_export($result, true), ['pre' => true]);
                    $routes[] = $result;
                }
            }
        }

        return $routes;
    }

    private function validRoute($fields): bool
    {
        $this->logger->notice(__METHOD__);
        $headers = [
            'Accept'           => 'application/json, text/javascript, */*; q=0.01',
            'Content-Type'     => 'application/x-www-form-urlencoded; charset=UTF-8',
            'Origin'           => 'https://flyasiana.com',
            'Referer'          => 'https://flyasiana.com/C/KR/EN/index',
            'X-Requested-With' => 'XMLHttpRequest',
        ];

//        $dataFrom = \Cache::getInstance()->get('ra_asiana_origins');
        $dataFrom = null;

        if (!$dataFrom || !is_array($dataFrom)) {
            $postData = [
                'seg'        => 'dep1',
                'bizType'    => 'RED',
                'depArrType' => 'DEP',
                'depAirport' => '',
                'depArea'    => '',
                'tripType'   => 'OW',
                'domIntType' => '',
            ];
            $this->http->RetryCount = 0;
            $this->http->PostURL("https://flyasiana.com/I/KR/EN/AreaAirportInfo.do?n\$eum=132119709619612770", $postData,
                $headers);

            if (strpos($this->http->currentUrl(), 'pc/noticeSystemMaintenance.html') !== false) {
                $msg = $this->http->FindSingleNode("//p[contains(.,'Note of a regular system maintenance on Sunday')]");

                if ($msg && $this->attempt > 1) {
                    throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
                }

                throw new \CheckRetryNeededException(5, 7);
            }

            $dataFrom = $this->http->JsonLog(null, 1, true);

            if (!$dataFrom) {
                $this->http->PostURL("https://flyasiana.com/I/KR/EN/AreaAirportInfo.do?n\$eum=104839599155119220", $postData,
                    $headers);
                $dataFrom = $this->http->JsonLog(null, 1, true);
            }
            $this->http->RetryCount = 2;

            // retries
            if ($this->isBadProxy() || $this->http->Response['code'] == 500) {
                if (ConfigValue(CONFIG_SITE_STATE) != SITE_STATE_DEBUG) {
                    $this->logger->debug("[attempt]: {$this->attempt}");

                    throw new \CheckRetryNeededException(5, 0);
                }
            }

            if (!empty($dataFrom) && isset($dataFrom['RouteCityAirportData'])) {
                \Cache::getInstance()->set('ra_asiana_origins', $dataFrom, 60 * 60 * 24);
            }
            $dataFrom = \Cache::getInstance()->get('ra_asiana_origins');

            if (!isset($dataFrom) || !is_array($dataFrom)) {
                if ($this->http->Response['code'] !== 500) {
                    $this->sendNotification("check origins // ZM");
                }

                throw new \CheckRetryNeededException(5, 0);
            }
        }

        if (isset($dataFrom) && is_array($dataFrom['RouteCityAirportData'])) {
            $inOrigins = false;

            foreach ($dataFrom['RouteCityAirportData'] as $routeCityAirportDatum) {
                foreach ($routeCityAirportDatum['cityAirportDatas'] as $origin) {
                    $this->logger->debug($origin['airport']);

                    if ($origin['airport'] === $fields['DepCode']) {
                        $this->depData = $origin;
                        $this->logger->debug(var_export($this->depData, true));
                        $inOrigins = true;

                        break 2;
                    }
                }
            }

            if (!$inOrigins) {
                $this->SetWarning($fields['DepCode'] . " is not in list of origins");

                return false;
            }

            $dataTo = null;

            if (!$dataTo || !is_array($dataTo)) {
                $postData = [
                    'seg'        => 'arr1',
                    'bizType'    => 'RED',
                    'depArrType' => 'ARR',
                    'depAirport' => $fields['DepCode'],
                    'depArea'    => '',
                    'tripType'   => 'OW',
                    'domIntType' => '',
                ];
                $this->http->RetryCount = 0;
                $this->http->PostURL("https://flyasiana.com/I/KR/EN/AreaAirportInfo.do?n\$eum=60804209401414350",
                    $postData, $headers);
                $dataTo = $this->http->JsonLog(null, 1, true);

                if (!$dataTo) {
                    $this->http->PostURL("https://flyasiana.com/I/KR/EN/AreaAirportInfo.do?n\$eum=11063851122421868",
                        $postData, $headers);
                    $dataTo = $this->http->JsonLog(null, 1, true);
                }
                $this->http->RetryCount = 2;

                // retries
                if ($this->isBadProxy()) {
                    if (ConfigValue(CONFIG_SITE_STATE) != SITE_STATE_DEBUG) {
                        $this->logger->debug("[attempt]: {$this->attempt}");

                        throw new \CheckRetryNeededException(5, 0);
                    }
                }

                if (!empty($dataTo) && isset($dataTo['RouteCityAirportData'])) {
                    \Cache::getInstance()->set('ra_asiana_destinations_' . $fields['DepCode'], $dataTo, 60 * 60 * 24);
                } else {
                    if ($this->http->Response['code'] !== 500) {
                        $this->sendNotification("check destinations // ZM");
                    }

                    throw new \CheckRetryNeededException(5, 0);
                }
            }

            if (is_array($dataTo['RouteCityAirportData'])) {
                $inDestinations = false;

                foreach ($dataTo['RouteCityAirportData'] as $routeCityAirportDatum) {
                    foreach ($routeCityAirportDatum['cityAirportDatas'] as $destination) {
                        $this->logger->debug($destination['airport']);

                        if ($destination['airport'] === $fields['ArrCode']) {
                            $this->arrData = $destination;
                            $this->logger->debug(var_export($this->arrData, true));
                            $inDestinations = true;

                            break 2;
                        }
                    }
                }

                if (!$inDestinations) {
                    $this->SetWarning($fields['ArrCode'] . " is not in list of destinations");

                    return false;
                }
            }
        }

        return true;
    }

    private function generateUUID()
    {
        $script = /** @lang JavaScript */
            "    
		    var d = new Date().getTime(),
			uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
				var r = (d + Math.random()*16)%16 | 0;
				d = Math.floor(d/16);
				return (c=='x' ? r : (r&0x7|0x8)).toString(16);
			});    
            sendResponseToPhp(uuid);
        ";
        $jsExecutor = $this->services->get(\AwardWallet\Common\Parsing\JsExecutor::class);
        $uuid = $jsExecutor->executeString($script);

        return $uuid;
    }

    private function sensorSensorData()
    {
        $this->logger->notice(__METHOD__);
        $form = $this->http->Form;
        $formURL = $this->http->FormURL;
        $referer = $this->http->currentUrl();

        $sensorPostUrl = $this->http->FindPreg("#_cf\.push\(\['_setAu',\s*'(/.+?)'\]\);#");

        if (!$sensorPostUrl) {
            $sensorPostUrl = $this->http->FindPreg('/src="(\/[^"]+)"><\/script><\/body>/');
        }

        if (!$sensorPostUrl) {
            $this->logger->error("sensor_data URL not found");

            return null;
        }
        $this->http->NormalizeURL($sensorPostUrl);

        $this->http->Form = $form;
        $this->http->FormURL = $formURL;
        $this->http->setDefaultHeader("Referer", $referer);

        $this->http->setCookie("_abck", $this->abck[$this->index]);

        if (count($this->sensorData) != count($this->secondSensorData)) {
            $this->logger->error("wrong sensor data values");

            return null;
        }

        $this->logger->notice("key: {$this->index}");

        sleep(1);
        $this->http->RetryCount = 0;
        $this->http->setUserAgent($this->userAgents[$this->index]);
        $sensorDataHeaders = [
            "Accept"       => "*/*",
            "Content-type" => "text/plain;charset=UTF-8",
            "Origin"       => "https://flyasiana.com",
            "Referer"      => $this->http->currentUrl(),
        ];
        $sensorData = [
            'sensor_data' => $this->sensorData[$this->index],
        ];
        $this->http->PostURL($sensorPostUrl, json_encode($sensorData), $sensorDataHeaders);
        sleep(1);
        $sensorData = [
            'sensor_data' => $this->secondSensorData[$this->index],
        ];

        $this->http->PostURL($sensorPostUrl, json_encode($sensorData), $sensorDataHeaders);
        $this->http->JsonLog();
        $this->http->RetryCount = 2;
        sleep(1);

        return $this->index;
    }
}
